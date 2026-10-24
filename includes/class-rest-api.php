<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPI_Rest_API {
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('wpi/v1', '/lead', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_submit_lead'],
            'permission_callback' => [__CLASS__, 'check_rate_limit'],
        ]);

        register_rest_route('wpi/v1', '/leads', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_get_leads'],
            'permission_callback' => [__CLASS__, 'check_admin_or_token'],
        ]);

        register_rest_route('wpi/v1', '/export', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_export_leads'],
            'permission_callback' => [__CLASS__, 'check_admin_or_token'],
        ]);

        register_rest_route('wpi/v1', '/test-webhook', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_test_webhook'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('wpi/v1', '/ping', [
            'methods'             => 'GET',
            'callback'            => function () {
                return rest_ensure_response(['status' => 'ok', 'version' => WPI_VERSION]);
            },
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * IP-based rate limiting
     */
    public static function check_rate_limit($request) {
        $ip = self::get_client_ip();
        $transient_key = 'wpi_rl_' . md5($ip);
        $attempts = (int) get_transient($transient_key);

        if ($attempts >= 25) {
            $lang = get_option('wpi_language', 'en');
            $msg  = ($lang === 'zh_CN')
                ? '您的 IP 提交过于频繁，请稍后再试。'
                : 'Too many inquiries submitted from your IP. Please try again later.';
            return new WP_Error(
                'rate_limit_exceeded',
                $msg,
                ['status' => 429]
            );
        }

        set_transient($transient_key, $attempts + 1, 300);
        return true;
    }

    /**
     * Handle lead submission with strict email validation and custom form data
     */
    public static function handle_submit_lead($request) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();

        $raw_email = sanitize_text_field($params['email'] ?? '');
        $raw_contact = sanitize_text_field($params['contact'] ?? '');

        // Extract and validate email (Must contain @)
        $email = '';
        if (self::has_valid_email($raw_email)) {
            $email = self::extract_email($raw_email);
        } elseif (self::has_valid_email($raw_contact)) {
            $email = self::extract_email($raw_contact);
        }

        $lang  = get_option('wpi_language', 'en');
        $is_zh = ($lang === 'zh_CN');

        // Strict validation: must have valid email with @
        if (empty($email)) {
            return new WP_Error(
                'invalid_email',
                $is_zh ? '必须提供包含“@”的有效业务邮箱（如 name@company.com）。' : 'A valid email address containing "@" (e.g. name@company.com) is required.',
                ['status' => 400]
            );
        }
        if (self::is_test_email($email)) {
            return new WP_Error(
                'test_email_rejected',
                $is_zh ? '不接受测试或临时邮箱，请提供真实可用的业务邮箱。' : 'Test email addresses (e.g. containing "test") are not accepted. Please provide a genuine business email.',
                ['status' => 400]
            );
        }

        $customer_name = sanitize_text_field($params['customer_name'] ?? '');
        $phone         = sanitize_text_field($params['phone'] ?? '');

        // If phone not explicitly provided, try extracting from contact string
        if (empty($phone) && !empty($raw_contact)) {
            $phone_candidate = trim(str_replace($email, '', $raw_contact));
            $clean_digits = preg_replace('/[^\d]/', '', $phone_candidate);
            if (strlen($clean_digits) >= 7) {
                $phone = $phone_candidate;
            }
        }

        $session_id   = sanitize_key($params['session_id'] ?? '');
        $product_name = sanitize_text_field($params['product_name'] ?? '');
        $source_url   = esc_url_raw($params['source_url'] ?? ($params['product_url'] ?? ''));
        $dwell_time   = (int) ($params['dwell_time'] ?? ($params['visit']['dwell_sec'] ?? 0));
        $referrer     = esc_url_raw($params['referrer'] ?? ($params['visit']['referrer'] ?? ''));
        $silent       = !empty($params['silent']);
        $is_final     = !empty($params['is_final']);
        $ip_address   = !empty($params['visit']['ip']) ? sanitize_text_field($params['visit']['ip']) : self::get_client_ip();
        $country      = !empty($params['visit']['country']) ? sanitize_text_field($params['visit']['country']) : self::get_client_country();

        // Extract answers array from form_data
        $raw_answers = $params['form_data'] ?? ($params['answers'] ?? []);
        if (is_string($raw_answers)) {
            $raw_answers = json_decode($raw_answers, true) ?: [];
        }
        $answers = is_array($raw_answers) ? $raw_answers : [];

        // Calculate Intent Grade purely from email, phone and answers
        $intent_grade = self::calculate_intent_grade($email, $phone, $answers);

        $table_name = WPI_DB::get_table_name();

        // Check if an existing lead exists for this session within the last 30 minutes
        $existing_lead = null;
        if (!empty($session_id)) {
            $existing_lead = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE session_id = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY id DESC LIMIT 1",
                $session_id
            ), ARRAY_A);
        }

        if ($existing_lead) {
            // Decode existing payload
            $old_payload = json_decode($existing_lead['payload_json'] ?? '', true) ?: [];
            $old_answers = $old_payload['answers'] ?? (json_decode($existing_lead['form_data'] ?? '', true) ?: []);
            $merged_answers = array_merge($old_answers, $answers);

            $payload_data = [
                'product_name' => $product_name ?: ($old_payload['product_name'] ?? ($existing_lead['product_name'] ?? '')),
                'answers'      => $merged_answers,
                'visit'        => [
                    'country'   => $country ?: ($old_payload['visit']['country'] ?? ''),
                    'ip'        => $ip_address,
                    'dwell_sec' => max($dwell_time, (int)($old_payload['visit']['dwell_sec'] ?? 0)),
                    'referrer'  => $referrer ?: ($old_payload['visit']['referrer'] ?? ''),
                ],
                'form_version' => WPI_VERSION,
            ];

            $wpdb->update(
                $table_name,
                [
                    'customer_name' => $customer_name ?: $existing_lead['customer_name'],
                    'email'         => $email ?: $existing_lead['email'],
                    'phone'         => $phone ?: $existing_lead['phone'],
                    'source_url'    => $source_url ?: ($existing_lead['source_url'] ?? ($existing_lead['product_url'] ?? '')),
                    'payload_json'  => wp_json_encode($payload_data),
                    'intent_grade'  => $intent_grade,
                ],
                ['id' => (int)$existing_lead['id']]
            );

            $lead_id = (int)$existing_lead['id'];
        } else {
            // New Lead Record (Clean 10 fields)
            $payload_data = [
                'product_name' => $product_name,
                'answers'      => $answers,
                'visit'        => [
                    'country'   => $country,
                    'ip'        => $ip_address,
                    'dwell_sec' => $dwell_time,
                    'referrer'  => $referrer,
                ],
                'form_version' => WPI_VERSION,
            ];

            $wpdb->insert(
                $table_name,
                [
                    'session_id'    => $session_id,
                    'customer_name' => $customer_name,
                    'email'         => $email,
                    'phone'         => $phone,
                    'source_url'    => $source_url,
                    'payload_json'  => wp_json_encode($payload_data),
                    'intent_grade'  => $intent_grade,
                    'status'        => 'new',
                    'created_at'    => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            $lead_id = $wpdb->insert_id;
        }

        $final_customer_name = $customer_name ?: ($existing_lead['customer_name'] ?? '');
        $final_phone         = $phone ?: ($existing_lead['phone'] ?? '');

        $lead_record = [
            'id'            => $lead_id,
            'session_id'    => $session_id,
            'customer_name' => $final_customer_name,
            'email'         => $email,
            'phone'         => $final_phone,
            'source_url'    => $source_url ?: ($existing_lead['source_url'] ?? ($existing_lead['product_url'] ?? '')),
            'product_name'  => $payload_data['product_name'],
            'payload_json'  => $payload_data,
            'intent_grade'  => $intent_grade,
            'status'        => $existing_lead['status'] ?? 'new',
            'created_at'    => $existing_lead['created_at'] ?? current_time('mysql'),
        ];

        // Deduplication & Anti-Spam Notification Policy
        $notify_transient_key = 'wpi_notified_' . md5($session_id);
        $already_notified     = !empty($session_id) && (bool) get_transient($notify_transient_key);

        // 1. Silent updates (intermediate probing questions) never dispatch webhook/email
        // 2. Final submission (all questions completed or user finished) always dispatches
        // 3. First-time submission dispatches if not silent and not notified yet
        $should_notify = false;
        if (!$silent) {
            if ($is_final) {
                $should_notify = true;
            } elseif (!$already_notified) {
                $should_notify = true;
            }
        }

        if ($should_notify) {
            WPI_Notifier::notify($lead_record);
            if (!empty($session_id)) {
                set_transient($notify_transient_key, 1, 1800);
            }
        }
        $default_success = $is_zh ? '感谢您的垂询！我们稍后将与您联系。' : 'Thank you! We will get in touch shortly.';
        return rest_ensure_response([
            'success'       => true,
            'message'       => get_option('wpi_lead_success_msg', $default_success),
            'lead_id'       => $lead_id,
            'customer_name' => $customer_name,
            'email'         => $email,
            'intent_grade'  => $intent_grade,
        ]);
    }

    /**
     * Admin Test Webhook endpoint
     */
    public static function handle_test_webhook($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $webhook_url = esc_url_raw($params['webhook_url'] ?? get_option('wpi_webhook_url', ''));
        $secret      = sanitize_text_field($params['secret'] ?? get_option('wpi_webhook_secret', ''));

        if (empty($webhook_url)) {
            return new WP_Error('no_webhook', 'Please enter a valid Feishu Webhook URL.', ['status' => 400]);
        }

        $mock_lead = [
            'id'            => 1024,
            'session_id'    => 'test_session_' . time(),
            'customer_name' => 'Alexander Wright',
            'email'         => 'a.wright@wright-aerospace.co.uk',
            'phone'         => '+44 20 7946 0958',
            'contact'       => 'a.wright@wright-aerospace.co.uk (+44 20 7946 0958) [Alexander Wright]',
            'product_name'  => 'High Precision CNC Flange Model-X',
            'product_url'   => home_url('/sample-page/'),
            'requirement'   => 'Need 3D CAD STEP files & ASTM 316 Test Certificate | Delivery to Port of Southampton',
            'quantity'      => '5,000+ pcs (OEM Bulk)',
            'form_data'     => [
                'Primary Application' => 'Aerospace Hydraulic Pipeline Assembly',
                'Material Requirement' => 'Stainless Steel 316L (High Corrosion Resistance)',
                'Estimated Project Timeframe' => 'Q4 2026 Mass Production',
            ],
            'intent_grade'  => 'S',
            'dwell_time'    => 115,
            'ip_address'    => self::get_client_ip(),
            'country'       => 'United Kingdom',
            'referrer'      => 'https://google.com/search?q=cnc+flange+aerospace+suppliers',
            'footprint'     => ['/', '/shop/', '/sample-page/'],
            'chat_log'      => [
                ['role' => 'bot', 'text' => 'Hi! Looking for factory-direct wholesale prices or CAD specs for "Sample Page"?'],
                ['role' => 'user', 'text' => 'Get Wholesale Price List'],
                ['role' => 'bot', 'text' => 'What is your estimated order quantity so we can match the right tier discount?'],
                ['role' => 'user', 'text' => '5,000+ pcs (OEM Bulk)'],
                ['role' => 'bot', 'text' => 'May I have your name and business Email with "@" so our engineer can send the CAD specs?'],
                ['role' => 'user', 'text' => 'Alexander Wright, a.wright@wright-aerospace.co.uk +44 20 7946 0958'],
            ],
        ];

        $res = WPI_Notifier::dispatch_feishu_card($webhook_url, $secret, $mock_lead);
        return rest_ensure_response($res);
    }
    public static function check_admin_or_token($request) {
        if (current_user_can('manage_options')) {
            return true;
        }
        $secret = get_option('wpi_webhook_secret', '');
        $token  = $request->get_param('token') ?: $request->get_header('x-wpi-token');
        if (!empty($secret) && !empty($token) && hash_equals($secret, $token)) {
            return true;
        }
        return new WP_Error('rest_forbidden', 'Unauthorized: Admin privileges or valid token required.', ['status' => 403]);
    }

    public static function handle_get_leads($request) {
        global $wpdb;
        $table = WPI_DB::get_table_name();

        $page   = max(1, (int) ($request->get_param('page') ?: 1));
        $limit  = min(100, max(1, (int) ($request->get_param('limit') ?: 20)));
        $offset = ($page - 1) * $limit;

        $status = sanitize_key($request->get_param('status') ?: '');
        $grade  = sanitize_text_field($request->get_param('grade') ?: '');

        $where = '1=1';
        if ($status) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }
        if ($grade) {
            $where .= $wpdb->prepare(' AND intent_grade = %s', $grade);
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
        $rows  = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);

        foreach ($rows as &$row) {
            $row['payload_json'] = !empty($row['payload_json']) ? json_decode($row['payload_json'], true) : [];
        }

        return rest_ensure_response([
            'success' => true,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'leads'   => $rows,
        ]);
    }

    public static function handle_export_leads($request) {
        global $wpdb;
        $table  = WPI_DB::get_table_name();
        $format = strtolower(sanitize_key($request->get_param('format') ?: 'csv'));
        $status = sanitize_key($request->get_param('status') ?: '');

        $where = '1=1';
        if ($status) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }

        $rows = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY id DESC", ARRAY_A);

        if ($format === 'json') {
            foreach ($rows as &$row) {
                $row['payload_json'] = !empty($row['payload_json']) ? json_decode($row['payload_json'], true) : [];
            }
            return rest_ensure_response($rows);
        }

        // Default to CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inquiry-leads-' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

        fputcsv($output, ['ID', 'Grade', 'Email', 'Phone', 'Customer Name', 'Product', 'Source URL', 'Key Answers Summary', 'Country', 'Dwell Sec', 'Status', 'Created At', 'Updated At']);

        foreach ($rows as $row) {
            $payload = !empty($row['payload_json']) ? json_decode($row['payload_json'], true) : [];
            $answers = $payload['answers'] ?? [];
            $qa_parts = [];
            if (is_array($answers)) {
                foreach ($answers as $q => $a) {
                    $qa_parts[] = '[' . $q . ']: ' . (is_array($a) ? implode(', ', $a) : $a);
                }
            }
            fputcsv($output, [
                $row['id'],
                $row['intent_grade'] ?? 'A',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['customer_name'] ?? '',
                $payload['product_name'] ?? '',
                $row['source_url'] ?? ($row['product_url'] ?? ''),
                implode(' | ', $qa_parts),
                $payload['visit']['country'] ?? '',
                $payload['visit']['dwell_sec'] ?? 0,
                $row['status'] ?? 'new',
                $row['created_at'] ?? '',
                $row['updated_at'] ?? '',
            ]);
        }
        fclose($output);
        exit;
    }

    public static function has_valid_email($str) {
        if (empty($str)) return false;
        return (bool) preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $str);
    }

    public static function extract_email($str) {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $str, $matches)) {
            return strtolower(trim($matches[0]));
        }
        return '';
    }
    public static function is_test_email($email) {
        if (empty($email) || strpos($email, '@') === false) {
            return false;
        }
        $parts = explode('@', strtolower(trim($email)));
        $user = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        $test_domains = ['test.com', 'example.com', 'sample.com', 'tempmail.com', 'testmail.com', 'fakemail.com'];
        if (in_array($domain, $test_domains, true)) {
            return true;
        }

        $tokens = preg_split('/[._+\-]/', $user);
        foreach ($tokens as $token) {
            if (preg_match('/^(test\d*|\d*test)$/i', $token)) {
                return true;
            }
        }
        return false;
    }


    public static function calculate_intent_grade($email, $phone, $form_data) {
        $text = strtolower(is_string($form_data) ? $form_data : wp_json_encode($form_data));
        $has_bulk = (bool)preg_match('/(5000\+|5,?000|bulk|container|mass|oem|odm)/i', $text);
        $has_cad_sample = (bool)preg_match('/(cad|step|drawing|sample|prototyp|spec|cert)/i', $text);
        $has_phone = !empty($phone);
        $has_email = !empty($email);

        if ($has_email && ($has_bulk || ($has_cad_sample && $has_phone))) {
            return 'S';
        }
        if ($has_email && ($has_bulk || $has_cad_sample || $has_phone)) {
            return 'A';
        }
        return 'B';
    }

    private static function get_client_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return sanitize_text_field(trim($parts[0]));
        }
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private static function get_client_country() {
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']);
        }
        if (!empty($_SERVER['GEOIP_COUNTRY_NAME'])) {
            return sanitize_text_field($_SERVER['GEOIP_COUNTRY_NAME']);
        }
        return '';
    }
}

WPI_Rest_API::init();
