<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPI_Notifier {
    /**
     * Notify administrators via email and external Feishu webhook
     */
    public static function notify($lead) {
        self::send_email($lead);
        self::send_webhook($lead);
    }

    /**
     * Send email notification via wp_mail
     */
    private static function send_email($lead) {
        $to = get_option('wpi_notify_email', get_option('admin_email'));
        if (empty($to)) {
            return;
        }

        $intent_grade = $lead['intent_grade'] ?? 'A';
        $grade_badge = ($intent_grade === 'S') ? '[S级高意向]' : (($intent_grade === 'B') ? '[B级普通]' : '[A级常规]');
        $customer_name   = !empty($lead['customer_name']) ? $lead['customer_name'] : 'Customer';
        $contact_display = !empty($lead['email']) ? $lead['email'] : ($lead['phone'] ?: 'N/A');

        $subject = sprintf(
            '%s [New Inquiry #%s] %s (%s) - %s',
            $grade_badge,
            $lead['id'] ?? 'NEW',
            esc_html($customer_name),
            esc_html($contact_display),
            !empty($lead['product_name']) ? esc_html($lead['product_name']) : 'General Inquiry'
        );

        $site_host = wp_parse_url($lead['source_url'] ?? '', PHP_URL_HOST) ?: (wp_parse_url(home_url(), PHP_URL_HOST) ?: 'Website');

        $body = "==================================================\n";
        $body .= "NEW INQUIRY LEAD TICKET (#" . ($lead['id'] ?? 'NEW') . ")\n";
        $body .= "==================================================\n";
        $body .= "Website: " . $site_host . "\n";
        $body .= "Name: " . $customer_name . "\n";
        $body .= "WhatsApp / Phone: " . ($lead['phone'] ?: 'N/A') . "\n";
        $body .= "Intent Grade: " . $intent_grade . " Level\n";
        $body .= "Product: " . ($lead['product_name'] ?: 'N/A') . "\n";
        $body .= "Product URL: " . ($lead['product_url'] ?: 'N/A') . "\n";

        if (!empty($lead['form_data'])) {
            $form_data = is_string($lead['form_data']) ? json_decode($lead['form_data'], true) : $lead['form_data'];
            if (is_array($form_data) && count($form_data) > 0) {
                $body .= "\nForm Requirements & Answers:\n";
                foreach ($form_data as $q => $a) {
                    $body .= "  • " . $q . ": " . (is_array($a) ? implode(', ', $a) : $a) . "\n";
                }
            }
        }

        $body .= "\nPage Dwell Time: " . ($lead['dwell_time'] ?? 0) . " seconds\n";
        $body .= "Buyer Location: " . ($lead['country'] ?: 'Unknown') . " (IP: " . ($lead['ip_address'] ?: 'N/A') . ")\n";
        $body .= "Referrer: " . ($lead['referrer'] ?: 'Direct / None') . "\n";
        $body .= "Time: " . current_time('mysql') . "\n\n";
        $body .= "View all inquiries in WP Admin > Inquiry Leads.\n";

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Send structured interactive card to Feishu
     */
    public static function send_webhook($lead) {
        $webhook_url = get_option('wpi_webhook_url', '');
        if (empty($webhook_url)) {
            return ['success' => false, 'message' => 'No webhook URL configured'];
        }

        $secret = get_option('wpi_webhook_secret', '');
        return self::dispatch_feishu_card($webhook_url, $secret, $lead);
    }

    /**
     * Build and dispatch Feishu Interactive Card
     */
    public static function dispatch_feishu_card($webhook_url, $secret, $lead) {
        $card_payload = self::build_feishu_card($lead);

        $payload = [
            'msg_type' => 'interactive',
            'card'     => $card_payload,
        ];

        if (!empty($secret)) {
            $timestamp = time();
            $string_to_sign = $timestamp . "\n" . $secret;
            $sign = base64_encode(hash_hmac('sha256', '', $string_to_sign, true));
            $payload['timestamp'] = (string) $timestamp;
            $payload['sign']      = $sign;
        }

        $res = wp_remote_post($webhook_url, [
            'timeout'  => 10,
            'blocking' => true,
            'headers'  => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'     => wp_json_encode($payload),
        ]);

        if (is_wp_error($res)) {
            return ['success' => false, 'message' => $res->get_error_message()];
        }

        $body = wp_remote_retrieve_body($res);
        $result = json_decode($body, true);

        if (isset($result['StatusCode']) && $result['StatusCode'] !== 0) {
            return ['success' => false, 'message' => $result['msg'] ?? 'Feishu Error', 'raw' => $body];
        }
        if (isset($result['code']) && $result['code'] !== 0) {
            return ['success' => false, 'message' => $result['msg'] ?? 'Feishu Error', 'raw' => $body];
        }

        return ['success' => true, 'response' => $result];
    }

    /**
     * Generate rich Feishu Interactive Card array with verified Email and Name
     */
    public static function build_feishu_card($lead) {
        $lead_id       = $lead['id'] ?? 'NEW';
        $intent_grade  = $lead['intent_grade'] ?? 'A';
        $email         = !empty($lead['email']) ? $lead['email'] : '';
        $phone         = !empty($lead['phone']) ? $lead['phone'] : '';
        $customer_name = !empty($lead['customer_name']) ? $lead['customer_name'] : '海外客户';

        $payload      = is_string($lead['payload_json'] ?? '') ? json_decode($lead['payload_json'], true) : ($lead['payload_json'] ?? []);
        $product_name = !empty($payload['product_name']) ? $payload['product_name'] : (!empty($lead['product_name']) ? $lead['product_name'] : '官网通用产品');
        $answers      = $payload['answers'] ?? (!empty($lead['form_data']) ? (is_string($lead['form_data']) ? json_decode($lead['form_data'], true) : $lead['form_data']) : []);
        $visit        = $payload['visit'] ?? [];
        $country      = !empty($visit['country']) ? $visit['country'] : (!empty($lead['country']) ? $lead['country'] : '海外客户');
        $dwell        = isset($visit['dwell_sec']) ? (int)$visit['dwell_sec'] : (isset($lead['dwell_time']) ? (int)$lead['dwell_time'] : 0);
        $source_url    = !empty($lead['source_url']) ? $lead['source_url'] : (!empty($lead['product_url']) ? $lead['product_url'] : home_url());
        $site_host     = wp_parse_url($source_url, PHP_URL_HOST) ?: (wp_parse_url(home_url(), PHP_URL_HOST) ?: 'Website');
        $site_home_url = 'https://' . $site_host;

        // Dynamic Header template & color based on grade (仅保留细彩色条，无文字)
        if ($intent_grade === 'S') {
            $template = 'red';
        } elseif ($intent_grade === 'B') {
            $template = 'blue';
        } else {
            $template = 'orange';
        }

        $header_title = '';

        // Section 1: Source Website & Customer Contact Info
        $contact_lines = [
            sprintf("**来源网站**：[%s](%s)", esc_html($site_host), esc_url($site_home_url)),
            sprintf("**客户称呼**：%s", esc_html($customer_name)),
            sprintf("**验证邮箱**：%s *(已验证)*", esc_html($email ?: '未提供')),
        ];
        if (!empty($phone)) {
            $contact_lines[] = sprintf("**WhatsApp/电话**：%s", esc_html($phone));
        }

        $req_lines = ["**采购规格与需求**"];
        if (!empty($source_url)) {
            $req_lines[] = sprintf("• **咨询产品**：[%s](%s)", esc_html($product_name), esc_url($source_url));
        } else {
            $req_lines[] = sprintf("• **咨询产品**：%s", esc_html($product_name));
        }
        if (is_array($answers) && count($answers) > 0) {
            foreach ($answers as $q => $a) {
                // Filter out email, name, and whatsapp options from purchasing specs
                if (preg_match('/(email|whatsapp|wechat|phone)/i', $q) && !preg_match('/(screen|touch|os|spec|size|qty|quantity|ram)/i', $q)) {
                    continue;
                }
                if (is_string($a) && (stripos($a, '@') !== false || stripos($a, 'Prefer Email') !== false || stripos($a, 'Chat on WhatsApp') !== false)) {
                    continue;
                }

                $clean_label = self::format_clean_label($q);
                $val_str = is_array($a) ? implode(', ', $a) : $a;
                $val_clean = trim(str_replace(["\r", "\n"], ' ', $val_str));
                $req_lines[] = sprintf("• **%s**：%s", $clean_label, $val_clean);
            }
        }
        // Section 3: Context Footnote (线索参考时间格式 yy-mm-dd HH:ii)
        $submit_time = !empty($lead['created_at']) ? date('y-m-d H:i', strtotime($lead['created_at'])) : current_time('y-m-d H:i');
        $footnote = sprintf("<font color='grey'>#%s · %s · 停留 %d 秒 · %s 提交</font>", $lead_id, esc_html($country), $dwell, $submit_time);

        $elements = [
            [
                'tag'  => 'div',
                'text' => [
                    'tag'     => 'lark_md',
                    'content' => implode("\n", $contact_lines),
                ],
            ],
            ['tag' => 'hr'],
            [
                'tag'  => 'div',
                'text' => [
                    'tag'     => 'lark_md',
                    'content' => implode("\n", $req_lines),
                ],
            ],
            ['tag' => 'hr'],
            [
                'tag'  => 'div',
                'text' => [
                    'tag'     => 'lark_md',
                    'content' => $footnote,
                ],
            ],
        ];
        return [
            'config' => [
                'wide_screen_mode' => true,
            ],
            'header' => [
                'title' => [
                    'tag'     => 'plain_text',
                    'content' => $header_title,
                ],
                'template' => $template,
            ],
            'elements' => $elements,
        ];
    }

    /**
     * Format verbose question prompts into concise 2-4 word specs labels
     */
    public static function format_clean_label($q) {
        $q_lower = strtolower(trim($q, " :：\t\n\r\0\x0B?？"));
        if (preg_match('/(hologram|showcase|fan|oled|pyramid|全息|展柜|风扇)/i', $q_lower)) return '展示形态';
        if (preg_match('/(optical|tracking|lens|viewing|视角|光学|角度|追踪)/i', $q_lower)) return '光学视角';
        if (preg_match('/(scenario|classroom|conference|meeting|用途|场景)/i', $q_lower)) return '应用场景';
        if (preg_match('/(capacity|voltage|kwh|ampere|ah|容量|电压|千瓦时)/i', $q_lower)) return '系统容量';
        if (preg_match('/(inverter|bms|protocol|can|rs485|逆变|协议|通信)/i', $q_lower)) return '逆变通信';
        if (preg_match('/(cell|cert|certification|un38|msds|ul1973|电芯|认证)/i', $q_lower)) return '电芯认证';
        if (preg_match('/(cct|lumen|cri|光效|色温|流明)/i', $q_lower)) return '光学参数';
        if (preg_match('/(dimming|dali|0-10v|triac|调光)/i', $q_lower)) return '调光控制';
        if (preg_match('/(wear|teeth|bucket|hardness|耐磨|斗齿|硬度)/i', $q_lower)) return '耐磨材质';
        if (preg_match('/(bonding|贴合)/i', $q_lower)) return '贴合工艺';
        if (preg_match('/(bezel|seam|拼缝)/i', $q_lower)) return '单元拼缝';
        if (preg_match('/(matrix|layout|array|拼接|阵列)/i', $q_lower)) return '拼接阵列';
        if (preg_match('/(bracket|rack|支架)/i', $q_lower)) return '安装支架';
        if (preg_match('/(processor|controller|signal|处理|信号)/i', $q_lower)) return '拼接处理';
        if (preg_match('/(table|桌体|茶几)/i', $q_lower)) return '桌体结构';
        if (preg_match('/(station_type|solar_bench|charging_station|solar.*station|光伏座椅|充电站)/i', $q_lower)) return '设备形态';
        if (preg_match('/(solar_battery|solar_power|autonomy|光伏储能|电池续航)/i', $q_lower)) return '光伏储能';
        if (preg_match('/(battery_type|battery|电池)/i', $q_lower)) return '电池规格';
        if (preg_match('/(charging_ports|wireless|usb_ports|port|充电接口|无线充电)/i', $q_lower)) return '充电接口';
        if (preg_match('/(smart_iot|auxiliary|hotspot|智慧物联|附加功能)/i', $q_lower)) return '智慧功能';
        if (preg_match('/(installation|enclosure|form_factor|structure|base|mounting|形态|结构|底座|安装)/i', $q_lower)) return '安装形态';
        if (preg_match('/(brightness.*cooling|cooling.*brightness|亮度.*散热)/i', $q_lower)) return '亮度与散热';
        if (preg_match('/(cooling|thermal|heat|散热|空调|风冷)/i', $q_lower)) return '散热方式';
        if (preg_match('/(brightness|nits|高亮|亮度)/i', $q_lower)) return '屏幕亮度';
        if (preg_match('/(waterproof|protection|ip65|ip66|防护|防水)/i', $q_lower)) return '防护等级';
        if (preg_match('/(touch|触控|触摸)/i', $q_lower)) return '触控方式';
        if (preg_match('/(screen|size|dimension|尺寸)/i', $q_lower)) return '屏幕尺寸';
        if (preg_match('/(feature|peripheral|object|外设|功能)/i', $q_lower)) return '功能特性';
        if (preg_match('/(player|playback|cms|os|system|android|windows|系统|播放)/i', $q_lower)) return '主控系统';
        if (preg_match('/(processor|ram|hardware|configuration|配置|硬件)/i', $q_lower)) return '配置要求';
        if (preg_match('/(quantity|volume|pcs|批量|采购量|数量)/i', $q_lower)) return '预估批量';
        if (preg_match('/(specs|drawing|model|需求|图纸)/i', $q_lower)) return '需求类型';
        return mb_substr(preg_replace('/[:?？：]+$/', '', trim($q)), 0, 12);
    }
}
