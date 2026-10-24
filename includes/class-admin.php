<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPI_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menus']);
        add_action('admin_init', [__CLASS__, 'handle_actions']);
    }

    public static function set_default_options() {
        if (get_option('wpi_enabled') === false) {
            update_option('wpi_enabled', '1');
            update_option('wpi_language', 'en');
            update_option('wpi_bot_name', 'Sales Specialist');
            update_option('wpi_welcome_msg', 'Hi! Looking for factory-direct wholesale prices or CAD specs for "{product_name}"?');
            update_option('wpi_default_welcome_msg', 'Hi there! Welcome to our official site. How can we help your project today?');
            update_option('wpi_lead_success_msg', 'Thank you! Our engineering and sales team will contact you within 2 hours with the specs and quotation.');
            update_option('wpi_form_questions', self::get_email_first_form_questions());
            update_option('wpi_faq_list', self::get_default_faqs());
            update_option('wpi_auto_popup_seconds', 15);
            update_option('wpi_exit_intent', 1);
            update_option('wpi_scroll_trigger', 50);
            update_option('wpi_notify_email', get_option('admin_email'));
            update_option('wpi_webhook_url', '');
            update_option('wpi_webhook_secret', '');
            update_option('wpi_whatsapp_number', '');
        }
    }

    /**
     * Default Probing Questions & Custom Form Flow
     */
    public static function get_default_form_questions() {
        return self::get_email_first_form_questions();
    }

    /**
     * Preset 1: Email-First Upfront (上来就要邮箱模式)
     */
    public static function get_email_first_form_questions() {
        return [
            [
                'id'       => 'email',
                'question' => 'Hello! Please enter your business Email to receive the factory wholesale price sheet and CAD drawings for {product_name}:',
                'type'     => 'email',
                'required' => true,
                'require_at_email' => true,
                'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                'placeholder'      => 'Enter your business Email (name@company.com)...',
            ],
            [
                'id'       => 'whatsapp_contact',
                'question' => 'Do you use WhatsApp or WeChat? Can you contact me on WhatsApp?',
                'type'     => 'optional_text',
                'options'  => ['Chat on WhatsApp', 'Prefer Email only'],
                'required' => false,
                'placeholder' => 'Enter your WhatsApp or click above...',
            ],
            [
                'id'       => 'screen_size',
                'question' => "What's the screen size you prefer? Please share with us or select below:",
                'type'     => 'choice_or_text',
                'options'  => ['10.1" - 15.6"', '21.5" - 27"', '32" - 43"', '55" and above'],
                'required' => false,
                'placeholder' => 'Preferred size or paste product link...',
            ],
            [
                'id'       => 'touchscreen',
                'question' => 'With touchscreen or no?',
                'type'     => 'choice',
                'options'  => ['With Touchscreen (Capacitive)', 'No (Display Only)'],
                'required' => false,
            ],
            [
                'id'       => 'os_preference',
                'question' => 'Android or Windows, which one OS you prefer?',
                'type'     => 'choice',
                'options'  => ['Android OS', 'Windows OS', 'Monitor Only (No OS)'],
                'required' => false,
            ],
            [
                'id'       => 'hardware_specs',
                'question' => 'Please let me know the detailed configuration requirements, I mean processor and RAM:',
                'type'     => 'choice_or_text',
                'options'  => [
                    'Standard (Recommend for me)',
                    'Android: 4GB RAM + 32GB ROM',
                    'Windows: Intel Core i5 / 8GB / 128GB',
                    'High Performance (Custom)'
                ],
                'required' => false,
                'placeholder' => 'e.g. Intel i5, 8GB RAM, 128GB SSD...',
            ]
        ];
    }
    public static function get_probing_form_questions() {
        return [
            [
                'id'       => 'specs_req',
                'question' => 'What specific specifications, models, or drawings do you need for {product_name}?',
                'type'     => 'choice_or_text',
                'options'  => ['Get Wholesale Price List', 'Download CAD / 3D STEP', 'Request Free Sample', 'Check Delivery & MOQ'],
                'required' => true,
            ],
            [
                'id'       => 'quantity',
                'question' => 'What is your estimated purchasing volume?',
                'type'     => 'choice',
                'options'  => ['< 500 pcs (Trial / Prototyping)', '500 - 5,000 pcs (Standard Batch)', '5,000+ pcs (OEM Mass Production)'],
                'required' => true,
            ],
            [
                'id'       => 'email',
                'question' => 'Please enter your business Email to receive the official quotation and drawings:',
                'type'     => 'email',
                'required' => true,
                'require_at_email' => true,
                'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so our engineer can send the specifications.',
                'placeholder'      => 'Enter your business Email with @...',
            ],
            [
                'id'       => 'phone_optional',
                'question' => 'Would you also like to share your WhatsApp or Phone number for instant engineering support?',
                'type'     => 'optional_text',
                'options'  => ['Skip this step'],
                'required' => false,
                'placeholder' => 'WhatsApp / Phone number (Optional)...',
            ]
        ];
    }

    public static function get_default_faqs() {
        return [
            [
                'keywords' => ['moq', 'minimum', 'order quantity', '起订量'],
                'answer'   => 'Our standard MOQ is 100-500 pcs depending on the model. Trial orders are welcome! Leave your Email or WhatsApp for exact model MOQ.',
            ],
            [
                'keywords' => ['sample', 'free sample', '样品', '打样'],
                'answer'   => 'Yes, we provide stock samples for quality testing. Custom prototyping takes 5-7 days. Please share your Email/WhatsApp to arrange a sample.',
            ],
            [
                'keywords' => ['price', 'cost', 'quote', 'quotation', 'how much', '价格', '报价'],
                'answer'   => 'Factory pricing depends on order volume and material specs. Leave your WhatsApp or Email and we will send our tiered price list within 2 hours.',
            ],
            [
                'keywords' => ['lead time', 'delivery', 'shipping', '交期', '发货', '物流'],
                'answer'   => 'Standard stock ships in 5-7 days. Mass production takes 20-30 days. We support FOB, CIF, and DDP door-to-door shipping.',
            ],
            [
                'keywords' => ['payment', 'pay', 't/t', 'lc', 'paypal', '付款'],
                'answer'   => 'We accept T/T (30% deposit, 70% before shipment), L/C at sight, PayPal (for samples), and Western Union.',
            ],
            [
                'keywords' => ['custom', 'oem', 'odm', 'drawing', 'logo', '定制'],
                'answer'   => 'Yes! We support full OEM/ODM customization based on STEP/CAD/PDF drawings and custom logo laser engraving.',
            ],
        ];
    }
    /**
     * Chinese Preset 1: Email/WeChat First Upfront (中文-邮箱与微信前置模式)
     */
    public static function get_chinese_email_first_form_questions() {
        return [
            [
                'id'       => 'email',
                'question' => '您好！请留下您的业务邮箱，我们将立即为您发送产品出厂批发报价单与 CAD 图纸资料：',
                'type'     => 'email',
                'required' => true,
                'require_at_email' => true,
                'prompt_error'     => '请提供包含“@”的有效邮箱地址（如 name@company.com），以便接收资料。',
                'placeholder'      => '请输入您的业务邮箱 (name@company.com)...',
            ],
            [
                'id'       => 'wechat_contact',
                'question' => '您平时常用微信沟通吗？如需技术经理 1 对 1 即时对接，欢迎留下您的微信号或手机：',
                'type'     => 'optional_text',
                'options'  => ['微信快速沟通', '仅邮件联系', '跳过此项'],
                'required' => false,
                'placeholder' => '微信号或手机（选填）...',
            ],
            [
                'id'       => 'screen_size',
                'question' => '请问您期望的产品尺寸或规格是多大？可直接选择或在下方输入：',
                'type'     => 'choice_or_text',
                'options'  => ['10.1 - 15.6寸', '21.5 - 27寸', '32 - 43寸', '55寸及以上', '定制非标尺寸'],
                'required' => false,
                'placeholder' => '期望尺寸或粘贴产品型号链接...',
            ],
            [
                'id'       => 'touchscreen',
                'question' => '是否需要触控功能？',
                'type'     => 'choice',
                'options'  => ['带电容触摸 (触控一体)', '纯显示 (无需触控)'],
                'required' => false,
            ],
            [
                'id'       => 'os_preference',
                'question' => '系统方面您倾向于安卓、Windows 还是单纯显示屏？',
                'type'     => 'choice',
                'options'  => ['安卓系统 (Android)', 'Windows 系统', '纯显示器 (无需系统)'],
                'required' => false,
            ],
            [
                'id'       => 'quantity',
                'question' => '请问您预计的项目采购量大概是多少？',
                'type'     => 'choice_or_text',
                'options'  => ['打样测试 (1-5台)', '小批量 (20-100台)', '工程/大批量 (200台以上)'],
                'required' => false,
                'placeholder' => '预计采购量（台/套）...',
            ]
        ];
    }

    /**
     * Chinese Preset 2: Probing Questions (中文-渐进式探询模式)
     */
    public static function get_chinese_probing_form_questions() {
        return [
            [
                'id'       => 'specs_req',
                'question' => '您好！请问您需要了解“{product_name}”的哪些产品信息或资料？',
                'type'     => 'choice_or_text',
                'options'  => ['获取出厂批发报价单', '索取 3D CAD/结构图纸', '申请样品试用/打样', '咨询生产交期与起订量'],
                'required' => true,
            ],
            [
                'id'       => 'quantity',
                'question' => '请问您预计的项目采购量大概是多少？',
                'type'     => 'choice',
                'options'  => ['< 50 台 (打样/原型测试)', '50 - 500 台 (标准批量)', '500+ 台 (深度定制大订单)'],
                'required' => true,
            ],
            [
                'id'       => 'email',
                'question' => '请留下您的业务邮箱，以便工程部向您发送专属报价和技术文件：',
                'type'     => 'email',
                'required' => true,
                'require_at_email' => true,
                'prompt_error'     => '请提供包含“@”的有效邮箱地址（如 name@company.com），以便接收资料。',
                'placeholder'      => '请输入您的业务邮箱与带 @ 邮箱...',
            ],
            [
                'id'       => 'phone_optional',
                'question' => '您是否也愿意留下微信或电话，方便技术经理为您提供 1 对 1 加急解答？',
                'type'     => 'optional_text',
                'options'  => ['跳过此项'],
                'required' => false,
                'placeholder' => '微信 / 手机号（选填）...',
            ]
        ];
    }

    /**
     * Chinese Default FAQs (中文常见问答 FAQ)
     */
    public static function get_chinese_faqs() {
        return [
            [
                'keywords' => ['起订量', 'moq', '最低起订', '多少起批'],
                'answer'   => '我们标准品起订量根据型号为 10-50 台起，支持小批量打样测试！留下您的邮箱或微信，我们为您发送具体型号的起订标准。',
            ],
            [
                'keywords' => ['样品', '打样', 'sample', '试样'],
                'answer'   => '支持打样！标准样机备有现货，定制打样周期约为 5-7 个工作日。请留下您的联系方式以便安排样机。',
            ],
            [
                'keywords' => ['价格', '报价', '多少钱', '成本', 'price', 'quote'],
                'answer'   => '工厂直销价格受采购量与配置选型影响。留下您的邮箱或微信，业务经理将在 2 小时内为您发送梯度批发底价表。',
            ],
            [
                'keywords' => ['交期', '发货', '物流', '多久发货', 'lead time', 'delivery'],
                'answer'   => '常规样机 3-5 天内发货，批量生产周期约 15-25 天。支持德邦、顺丰专线及全国物流专车送达。',
            ],
            [
                'keywords' => ['付款', '结算', '开票', 'payment', '发票'],
                'answer'   => '支持企业对公转账、银行电汇，支持开具 13% 增值税专用发票；批量订单支持 30% 预付定金、验货合格后结清尾款发货。',
            ],
            [
                'keywords' => ['定制', 'oem', 'odm', '图纸', '开模', 'logo'],
                'answer'   => '支持深度 OEM/ODM 定制！支持客制化丝印 LOGO、外壳钣金开模、主板接口定制及根据 CAD/STEP 图纸打样。',
            ],
        ];
    }

    /**
     * Frontend dynamic i18n dictionary for English and Chinese
     */
    public static function get_frontend_i18n_strings() {
        return [
            'en' => [
                'bot_name'            => 'Sales Specialist',
                'status_text'         => 'Online | Direct Factory Reply',
                'context_tag'         => 'Currently Viewing:',
                'input_placeholder'   => 'Type WhatsApp, Email or question...',
                'footer_note'         => 'Secured & Direct to Manufacturer',
                'close_title'         => 'Minimize',
                'launcher_aria'       => 'Open Inquiry Chat',
                'placeholder_default' => 'Type your answer or click below...',
                'placeholder_email'   => 'Enter your business Email (name@company.com)...',
                'placeholder_notes'   => 'Type additional notes for our engineer...',
                'note_added'          => 'Note added to your inquiry.',
                'faq_follow_up'       => 'By the way, could you also help with our previous question:',
                'email_required_error'=> 'Please provide a valid business email containing "@" (e.g. name@company.com) so our engineer can send the specifications.',
                'email_test_error'    => 'Please provide a reachable business email address. Test accounts (e.g. containing "test") cannot receive official quotes and specifications.',
                'email_fallback'      => 'Please enter your business Email containing "@" so our engineer can send the specifications.',
                'welcome_msg'         => 'Hi! Looking for factory-direct wholesale prices or CAD specs for "{product_name}"?',
                'default_welcome_msg' => 'Hi there! Welcome to our official site. How can we help your project today?',
                'lead_success_msg'    => 'Thank you! Our engineering and sales team will contact you within 2 hours with the specs and quotation.',
            ],
            'zh' => [
                'bot_name'            => '业务顾问',
                'status_text'         => '在线 | 源头工厂直通',
                'context_tag'         => '正在浏览：',
                'input_placeholder'   => '输入微信/电话、邮箱或提问...',
                'footer_note'         => '官方直通工厂 · 商业隐私保护',
                'close_title'         => '最小化',
                'launcher_aria'       => '打开询盘咨询',
                'placeholder_default' => '请输入您的回答或点击下方选项...',
                'placeholder_email'   => '请输入您的业务邮箱 (name@company.com)...',
                'placeholder_notes'   => '可继续输入更多规格要求或留言...',
                'note_added'          => '已为您补充备注信息。',
                'faq_follow_up'       => '顺便请教一下，关于刚才的问题方便告知我们吗：',
                'email_required_error'=> '请提供包含“@”的有效业务邮箱（如 name@company.com），以便工程师为您发送规格书与报价。',
                'email_test_error'    => '请提供真实可用的业务邮箱，测试邮箱无法接收官方正式报价与图纸。',
                'email_fallback'      => '请留下您的业务邮箱，以便我们工程师为您发送详细图纸和报价单。',
                'welcome_msg'         => '您好！想获取“{product_name}”的出厂批发底价或 3D/CAD 规格图纸吗？',
                'default_welcome_msg' => '您好！欢迎访问我们官方网站，请问有什么可以协助您的项目？',
                'lead_success_msg'    => '感谢您的垂询！我们的技术与业务团队将在 2 小时内与您联系，提供规格书与报价单。',
            ],
        ];
    }

    public static function register_menus() {
        global $wpdb;
        $table = WPI_DB::get_table_name();
        $new_count = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $new_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'new'");
        }

        $badge = $new_count > 0 ? sprintf(' <span class="awaiting-mod">%d</span>', $new_count) : '';

        add_menu_page(
            '询盘线索 (Inquiry Leads)',
            '询盘线索' . $badge,
            'manage_options',
            'wpi-leads',
            [__CLASS__, 'render_leads_page'],
            'dashicons-format-chat',
            26
        );

        add_submenu_page(
            'wpi-leads',
            '全部询盘工单',
            '全部工单',
            'manage_options',
            'wpi-leads',
            [__CLASS__, 'render_leads_page']
        );

        add_submenu_page(
            'wpi-leads',
            '表单与追问语料 (Form & Questions)',
            '表单与语料',
            'manage_options',
            'wpi-form-builder',
            [__CLASS__, 'render_form_builder_page']
        );

        add_submenu_page(
            'wpi-leads',
            '机器人设置 (Chatbot Settings)',
            '机器人设置',
            'manage_options',
            'wpi-settings',
            [__CLASS__, 'render_settings_page']
        );
    }
    public static function handle_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table = WPI_DB::get_table_name();

        // Handle CSV Export
        if (isset($_GET['page']) && $_GET['page'] === 'wpi-leads' && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            check_admin_referer('wpi_export_csv');
            $status_filter = sanitize_key($_GET['status'] ?? '');
            $where = '1=1';
            if ($status_filter && in_array($status_filter, ['new', 'contacted'], true)) {
                $where .= $wpdb->prepare(' AND status = %s', $status_filter);
            }

            $rows = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY id DESC", ARRAY_A);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=inquiry-leads-' . date('Y-m-d') . ($status_filter ? '-' . $status_filter : '') . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens Chinese/Special characters without garbled text
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, [
                'ID',
                '意向等级 (Grade)',
                '客户邮箱 (Email)',
                'WhatsApp/电话 (Phone)',
                '客户称呼 (Name)',
                '咨询产品 (Product)',
                '来源页面 (Source URL)',
                '采购需求与问答明细 (Q&A Summary)',
                '国家地区 (Country)',
                '停留秒数 (Dwell Sec)',
                '跟进状态 (Status)',
                '提交时间 (Created At)',
                '更新时间 (Updated At)'
            ]);

            foreach ($rows as $row) {
                $payload   = !empty($row['payload_json']) ? json_decode($row['payload_json'], true) : [];
                $answers   = $payload['answers'] ?? (!empty($row['form_data']) ? json_decode($row['form_data'], true) : []);
                $visit     = $payload['visit'] ?? [];
                $prod_name = $payload['product_name'] ?? ($row['product_name'] ?? '');

                $qa_parts = [];
                if (is_array($answers) && count($answers) > 0) {
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
                    $prod_name,
                    $row['source_url'] ?? ($row['product_url'] ?? ''),
                    implode(' | ', $qa_parts),
                    $visit['country'] ?? ($row['country'] ?? ''),
                    $visit['dwell_sec'] ?? ($row['dwell_time'] ?? 0),
                    $row['status'] ?? 'new',
                    $row['created_at'] ?? '',
                    $row['updated_at'] ?? '',
                ]);
            }
            fclose($output);
            exit;
        }

        // Handle JSON Export of Leads
        if (isset($_GET['page']) && $_GET['page'] === 'wpi-leads' && isset($_GET['action']) && $_GET['action'] === 'export_json') {
            check_admin_referer('wpi_export_json');
            $status_filter = sanitize_key($_GET['status'] ?? '');
            $where = '1=1';
            if ($status_filter && in_array($status_filter, ['new', 'contacted'], true)) {
                $where .= $wpdb->prepare(' AND status = %s', $status_filter);
            }

            $rows = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY id DESC", ARRAY_A);

            $export_list = [];
            foreach ($rows as $row) {
                $row['payload_json'] = !empty($row['payload_json']) ? json_decode($row['payload_json'], true) : [];
                $export_list[] = $row;
            }
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=inquiry-leads-' . date('Y-m-d') . ($status_filter ? '-' . $status_filter : '') . '.json');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo wp_json_encode($export_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Export Entire Corpus JSON
        if (isset($_GET['page']) && $_GET['page'] === 'wpi-form-builder' && isset($_GET['action']) && $_GET['action'] === 'export_corpus') {
            check_admin_referer('wpi_export_corpus');
            $corpus_data = [
                'bot_name'           => get_option('wpi_bot_name', 'Sales Specialist'),
                'welcome_msg'        => get_option('wpi_welcome_msg', ''),
                'default_welcome_msg'=> get_option('wpi_default_welcome_msg', ''),
                'form_questions'     => get_option('wpi_form_questions', self::get_default_form_questions()),
                'faq_list'           => get_option('wpi_faq_list', self::get_default_faqs()),
                'exported_at'        => current_time('mysql'),
                'version'            => WPI_VERSION,
            ];

            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=wp-chatbot-corpus-' . date('Y-m-d') . '.json');
            echo wp_json_encode($corpus_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Handle Preset Switching
        if (isset($_GET['page']) && $_GET['page'] === 'wpi-form-builder' && isset($_GET['action']) && $_GET['action'] === 'apply_preset' && isset($_GET['preset'])) {
            check_admin_referer('wpi_apply_preset');
            $preset = sanitize_key($_GET['preset']);
            if ($preset === 'email_first') {
                update_option('wpi_form_questions', self::get_email_first_form_questions());
                add_settings_error('wpi_messages', 'wpi_preset', '已成功应用「英文 - 上来就要邮箱」预设！第一步直接索要带 @ 邮箱并秒级落库推飞书。', 'updated');
            } elseif ($preset === 'probing') {
                update_option('wpi_form_questions', self::get_probing_form_questions());
                add_settings_error('wpi_messages', 'wpi_preset', '已成功应用「英文 - 渐进式多轮探询」预设！先探询需求与量，第三步索要邮箱。', 'updated');
            } elseif ($preset === 'zh_email_first') {
                update_option('wpi_form_questions', self::get_chinese_email_first_form_questions());
                add_settings_error('wpi_messages', 'wpi_preset', '已成功应用「中文 - 邮箱/微信前置」预设！第一步索要带 @ 邮箱或微信并秒级落库推飞书。', 'updated');
            } elseif ($preset === 'zh_probing') {
                update_option('wpi_form_questions', self::get_chinese_probing_form_questions());
                add_settings_error('wpi_messages', 'wpi_preset', '已成功应用「中文 - 渐进式探询」预设！先探询规格意向与数量，再索要联系方式。', 'updated');
            } elseif ($preset === 'zh_faqs') {
                update_option('wpi_faq_list', self::get_chinese_faqs());
                add_settings_error('wpi_messages', 'wpi_preset', '已成功重置为「中文常见问答 FAQ 语料库」（起订量、打样、价格、交期、付款、定制等）。', 'updated');
            } elseif ($preset === 'en_faqs') {
                update_option('wpi_faq_list', self::get_default_faqs());
                add_settings_error('wpi_messages', 'wpi_preset', '已成功重置为「英文常见问答 FAQ 语料库」（MOQ, Sample, Price, Lead Time, Payment, OEM/ODM）。', 'updated');
            }
            wp_safe_redirect(admin_url('admin.php?page=wpi-form-builder'));
            exit;
        }

        // Handle Quick Language Reset in Settings
        if (isset($_GET['page']) && $_GET['page'] === 'wpi-settings' && isset($_GET['action']) && $_GET['action'] === 'load_i18n_defaults' && isset($_GET['lang'])) {
            check_admin_referer('wpi_load_i18n');
            $target_lang = sanitize_key($_GET['lang']);
            if ($target_lang === 'zh_CN' || $target_lang === 'zh') {
                update_option('wpi_language', 'zh_CN');
                update_option('wpi_bot_name', '业务顾问');
                update_option('wpi_welcome_msg', '您好！想获取“{product_name}”的出厂批发底价或 3D/CAD 规格图纸吗？');
                update_option('wpi_default_welcome_msg', '您好！欢迎访问我们官方网站，请问有什么可以协助您的项目？');
                update_option('wpi_lead_success_msg', '感谢您的垂询！我们的技术与业务团队将在 2 小时内与您联系，提供规格书与报价单。');
                add_settings_error('wpi_messages', 'wpi_i18n_loaded', '已成功将机器人语言设置为「简体中文」，并加载了中文预设欢迎语与客服名称。', 'updated');
            } else {
                update_option('wpi_language', 'en');
                update_option('wpi_bot_name', 'Sales Specialist');
                update_option('wpi_welcome_msg', 'Hi! Looking for factory-direct wholesale prices or CAD specs for "{product_name}"?');
                update_option('wpi_default_welcome_msg', 'Hi there! Welcome to our official site. How can we help your project today?');
                update_option('wpi_lead_success_msg', 'Thank you! Our engineering and sales team will contact you within 2 hours with the specs and quotation.');
                add_settings_error('wpi_messages', 'wpi_i18n_loaded', 'Language set to English and loaded default English greetings.', 'updated');
            }
            wp_safe_redirect(admin_url('admin.php?page=wpi-settings'));
            exit;
        }

        // Handle Status Update / Delete
        if (isset($_GET['page']) && $_GET['page'] === 'wpi-leads' && !empty($_GET['lead_id']) && !empty($_GET['lead_action'])) {
            check_admin_referer('wpi_lead_action');
            $id = (int) $_GET['lead_id'];
            $act = sanitize_key($_GET['lead_action']);

            if ($act === 'mark_contacted') {
                $wpdb->update($table, ['status' => 'contacted'], ['id' => $id]);
            } elseif ($act === 'delete') {
                $wpdb->delete($table, ['id' => $id]);
            }
            wp_safe_redirect(admin_url('admin.php?page=wpi-leads'));
            exit;
        }

        // Handle Form Builder Save
        if (isset($_POST['wpi_save_form_builder'])) {
            check_admin_referer('wpi_save_form_nonce');

            if (!empty($_POST['wpi_form_questions_json'])) {
                $decoded = json_decode(stripslashes($_POST['wpi_form_questions_json']), true);
                if (is_array($decoded)) {
                    update_option('wpi_form_questions', $decoded);
                }
            }

            if (!empty($_POST['wpi_import_corpus_json'])) {
                $imported = json_decode(stripslashes($_POST['wpi_import_corpus_json']), true);
                if (is_array($imported)) {
                    if (!empty($imported['form_questions']) && is_array($imported['form_questions'])) {
                        update_option('wpi_form_questions', $imported['form_questions']);
                    }
                    if (!empty($imported['faq_list']) && is_array($imported['faq_list'])) {
                        update_option('wpi_faq_list', $imported['faq_list']);
                    }
                    if (!empty($imported['welcome_msg'])) {
                        update_option('wpi_welcome_msg', sanitize_textarea_field($imported['welcome_msg']));
                    }
                    if (!empty($imported['bot_name'])) {
                        update_option('wpi_bot_name', sanitize_text_field($imported['bot_name']));
                    }
                }
            }

            add_settings_error('wpi_messages', 'wpi_form_saved', 'Custom form questions & corpus updated successfully.', 'updated');
        }

        // Handle General Settings Save
        if (isset($_POST['wpi_save_settings'])) {
            check_admin_referer('wpi_save_settings_nonce');

            update_option('wpi_enabled', isset($_POST['wpi_enabled']) ? '1' : '0');
            update_option('wpi_language', sanitize_key($_POST['wpi_language'] ?? 'en'));
            update_option('wpi_bot_name', sanitize_text_field($_POST['wpi_bot_name'] ?? 'Sales Specialist'));
            update_option('wpi_welcome_msg', sanitize_textarea_field($_POST['wpi_welcome_msg'] ?? ''));
            update_option('wpi_default_welcome_msg', sanitize_textarea_field($_POST['wpi_default_welcome_msg'] ?? ''));
            update_option('wpi_auto_popup_seconds', (int) ($_POST['wpi_auto_popup_seconds'] ?? 15));
            update_option('wpi_exit_intent', isset($_POST['wpi_exit_intent']) ? 1 : 0);
            update_option('wpi_scroll_trigger', (int) ($_POST['wpi_scroll_trigger'] ?? 50));
            update_option('wpi_lead_success_msg', sanitize_textarea_field($_POST['wpi_lead_success_msg'] ?? ''));
            update_option('wpi_notify_email', sanitize_email($_POST['wpi_notify_email'] ?? ''));
            update_option('wpi_webhook_url', esc_url_raw($_POST['wpi_webhook_url'] ?? ''));
            update_option('wpi_webhook_secret', sanitize_text_field($_POST['wpi_webhook_secret'] ?? ''));
            update_option('wpi_whatsapp_number', sanitize_text_field($_POST['wpi_whatsapp_number'] ?? ''));
            if (isset($_POST['wpi_update_url'])) {
                update_option('wpi_update_url', esc_url_raw($_POST['wpi_update_url']));
            }
            if (isset($_POST['wpi_update_token'])) {
                update_option('wpi_update_token', sanitize_text_field($_POST['wpi_update_token']));
            }
            if (!empty($_POST['wpi_faq_json'])) {
                $decoded_faq = json_decode(stripslashes($_POST['wpi_faq_json']), true);
                if (is_array($decoded_faq)) {
                    update_option('wpi_faq_list', $decoded_faq);
                }
            }

            add_settings_error('wpi_messages', 'wpi_saved', 'Settings saved successfully.', 'updated');
        }
    }

    /**
     * Custom Form & Question Builder Page
     */
    public static function render_form_builder_page() {
        settings_errors('wpi_messages');
        $form_questions = get_option('wpi_form_questions', self::get_default_form_questions());
        $export_url = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=export_corpus'), 'wpi_export_corpus');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Custom Form & Probing Questions (发散提问与自定义表单)</h1>
            <a href="<?php echo esc_url($export_url); ?>" class="page-title-action">Export Entire Corpus (导出语料库 JSON)</a>
            <hr class="wp-header-end">

            <p style="font-size:14px;color:#4b5563;">
                配置机器人的发散追问流（Probing Question Flow）。支持配置<strong>必填带 @ 的邮箱校验</strong>、多选项卡片以及自定义开放式业务提问。
            </p>

            <?php
            $email_first_url    = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=apply_preset&preset=email_first'), 'wpi_apply_preset');
            $probing_url        = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=apply_preset&preset=probing'), 'wpi_apply_preset');
            $zh_email_first_url = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=apply_preset&preset=zh_email_first'), 'wpi_apply_preset');
            $zh_probing_url     = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=apply_preset&preset=zh_probing'), 'wpi_apply_preset');
            $zh_faqs_url        = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=apply_preset&preset=zh_faqs'), 'wpi_apply_preset');
            $en_faqs_url        = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=apply_preset&preset=en_faqs'), 'wpi_apply_preset');
            ?>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;padding:14px 18px;border-radius:8px;margin-bottom:18px;">
                <div style="margin-bottom:8px;">
                    <strong style="color:#0369a1;font-size:14px;">快捷业务问答语料预设（点击一键切换策略与语言）：</strong>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                    <span style="font-weight:600;color:#0f172a;margin-right:2px;">中文模式：</span>
                    <a href="<?php echo esc_url($zh_email_first_url); ?>" class="button button-primary" style="background:#0284c7;border-color:#0284c7;" onclick="return confirm('确定应用「中文 - 邮箱/微信前置」预设吗？将覆盖当前表单问题。');">启用「中文 - 邮箱/微信前置」模式 (推荐)</a>
                    <a href="<?php echo esc_url($zh_probing_url); ?>" class="button button-secondary" onclick="return confirm('确定应用「中文 - 渐进式探询」预设吗？将覆盖当前表单问题。');">启用「中文 - 渐进式探询」模式</a>
                    <a href="<?php echo esc_url($zh_faqs_url); ?>" class="button button-secondary" onclick="return confirm('确定重置为中文常见问答 FAQ 语料库吗？将覆盖当前 FAQ 列表。');">加载中文 FAQ 语料</a>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:10px;padding-top:10px;border-top:1px dashed #bae6fd;">
                    <span style="font-weight:600;color:#0f172a;margin-right:2px;">英文外贸：</span>
                    <a href="<?php echo esc_url($email_first_url); ?>" class="button button-secondary" onclick="return confirm('Apply English Email-First upfront preset? This will overwrite current form questions.');">启用「英文 - 上来就要邮箱」模式</a>
                    <a href="<?php echo esc_url($probing_url); ?>" class="button button-secondary" onclick="return confirm('Apply English Probing questions preset? This will overwrite current form questions.');">启用「英文 - 渐进式多轮探询」模式</a>
                    <a href="<?php echo esc_url($en_faqs_url); ?>" class="button button-secondary" onclick="return confirm('Reset to English default FAQs? This will overwrite current FAQ list.');">加载英文 FAQ 语料</a>
                </div>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('wpi_save_form_nonce'); ?>

                <div style="background:#ffffff;border:1px solid #ccd0d4;padding:16px 20px;border-radius:8px;margin-bottom:20px;">
                    <h2 style="margin-top:0;">正在启用的发散追问表单列表</h2>
                    <table class="widefat fixed striped" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th style="width:50px;">顺序</th>
                                <th style="width:120px;">字段 ID</th>
                                <th>追问话术 (支持 {product_name} 占位符)</th>
                                <th style="width:140px;">问题类型</th>
                                <th style="width:220px;">选项 / 校验规则</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($form_questions as $index => $q) : ?>
                                <tr>
                                    <td><strong>#<?php echo $index + 1; ?></strong></td>
                                    <td><code><?php echo esc_html($q['id']); ?></code></td>
                                    <td><strong><?php echo esc_html($q['question']); ?></strong></td>
                                    <td>
                                        <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
                                            <?php echo esc_html($q['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($q['require_at_email'])) : ?>
                                            <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;">
                                                强校验: 必须包含 @
                                            </span>
                                        <?php elseif (!empty($q['options'])) : ?>
                                            <span style="font-size:11px;color:#4b5563;">
                                                <?php echo count($q['options']); ?> 个快捷选项 (Chips)
                                            </span>
                                        <?php else : ?>
                                            <span style="color:#9ca3af;font-size:11px;">自由文本</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="background:#ffffff;border:1px solid #ccd0d4;padding:16px 20px;border-radius:8px;margin-bottom:20px;">
                    <h2 style="margin-top:0;">编写与编辑发散问答流 (JSON 格式)</h2>
                    <p class="description">你可以直接在这里修改问题文案、添加新的追问环节或修改快捷选项：</p>
                    <textarea name="wpi_form_questions_json" rows="14" class="large-text code"><?php echo esc_textarea(wp_json_encode($form_questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                </div>

                <div style="background:#ffffff;border:1px solid #ccd0d4;padding:16px 20px;border-radius:8px;margin-bottom:20px;">
                    <h2 style="margin-top:0;">语料库一键导入 (Import Corpus)</h2>
                    <p class="description">粘贴外部导出的问答语料库 JSON（包含问卷流、FAQ、开场白），一键覆盖导入：</p>
                    <textarea name="wpi_import_corpus_json" rows="5" class="large-text code" placeholder='{"form_questions": [...], "faq_list": [...]}'></textarea>
                </div>

                <p class="submit">
                    <button type="submit" name="wpi_save_form_builder" class="button button-primary button-large">Save Questions & Corpus (保存表单语料)</button>
                </p>
            </form>
        </div>
        <?php
    }

    public static function render_leads_page() {
        global $wpdb;
        $table = WPI_DB::get_table_name();

        $status_filter = sanitize_key($_GET['status'] ?? '');
        $where = '1=1';
        if ($status_filter && in_array($status_filter, ['new', 'contacted'], true)) {
            $where .= $wpdb->prepare(' AND status = %s', $status_filter);
        }

        $leads = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT 200");
        $total_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $new_count   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'new'");
        $current_status = $status_filter ? '&status=' . urlencode($status_filter) : '';
        $export_csv_url  = wp_nonce_url(admin_url('admin.php?page=wpi-leads&action=export_csv' . $current_status), 'wpi_export_csv');
        $export_json_url = wp_nonce_url(admin_url('admin.php?page=wpi-leads&action=export_json' . $current_status), 'wpi_export_json');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">询盘线索与表单数据 (Inquiry Leads & Forms)</h1>
            <a href="<?php echo esc_url($export_csv_url); ?>" class="page-title-action" style="font-weight:600;color:#0369a1;background:#e0f2fe;border-color:#bae6fd;">导出 Excel / CSV 表单</a>
            <a href="<?php echo esc_url($export_json_url); ?>" class="page-title-action" style="font-weight:600;color:#334155;">导出 JSON 结构数据</a>
            <hr class="wp-header-end">

            <ul class="subsubsub">
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wpi-leads')); ?>" class="<?php echo $status_filter === '' ? 'current' : ''; ?>">全部线索 (All) <span class="count">(<?php echo $total_count; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wpi-leads&status=new')); ?>" class="<?php echo $status_filter === 'new' ? 'current' : ''; ?>">待跟进 (New) <span class="count">(<?php echo $new_count; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wpi-leads&status=contacted')); ?>" class="<?php echo $status_filter === 'contacted' ? 'current' : ''; ?>">已联系 (Contacted) <span class="count">(<?php echo $total_count - $new_count; ?>)</span></a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped" style="margin-top:12px;">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th style="width:85px;">Grade</th>
                        <th style="width:200px;">Customer Contact</th>
                        <th>Target Product</th>
                        <th>Custom Form QA / Requirements</th>
                        <th style="width:130px;">Visitor Info</th>
                        <th style="width:80px;">Status</th>
                        <th style="width:140px;">Created At</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)) : ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:24px;">No inquiries captured yet. Open the frontend site to test the chatbot!</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($leads as $lead) :
                            $mark_url = wp_nonce_url(admin_url('admin.php?page=wpi-leads&lead_id=' . $lead->id . '&lead_action=mark_contacted'), 'wpi_lead_action');
                            $del_url    = wp_nonce_url(admin_url('admin.php?page=wpi-leads&lead_id=' . $lead->id . '&lead_action=delete'), 'wpi_lead_action');
                            $payload    = !empty($lead->payload_json) ? json_decode($lead->payload_json, true) : [];
                            $answers    = $payload['answers'] ?? (!empty($lead->form_data) ? json_decode($lead->form_data, true) : []);
                            $visit      = $payload['visit'] ?? [];
                            $prod_name  = !empty($payload['product_name']) ? $payload['product_name'] : (!empty($lead->product_name) ? $lead->product_name : 'General Inquiry');
                            $src_url    = !empty($lead->source_url) ? $lead->source_url : (!empty($lead->product_url) ? $lead->product_url : '');
                            $country    = !empty($visit['country']) ? $visit['country'] : (!empty($lead->country) ? $lead->country : 'Local/Unknown');
                            $dwell      = isset($visit['dwell_sec']) ? (int)$visit['dwell_sec'] : (isset($lead->dwell_time) ? (int)$lead->dwell_time : 0);
                            $grade      = $lead->intent_grade ?? 'A';
                            $disp_name  = !empty($lead->customer_name) ? $lead->customer_name : 'Customer';
                            $disp_email = $lead->email ?: 'N/A';
                            ?>
                            <tr>
                                <td><strong>#<?php echo (int) $lead->id; ?></strong></td>
                                <td>
                                    <?php if ($grade === 'S') : ?>
                                        <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;">S级大单</span>
                                    <?php elseif ($grade === 'B') : ?>
                                        <span style="background:#e0f2fe;color:#075985;padding:2px 8px;border-radius:10px;font-size:11px;">B级留资</span>
                                    <?php else : ?>
                                        <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">A级常规</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($disp_name); ?></strong><br>
                                    <strong style="color:#1d4ed8;font-size:12.5px;"><?php echo esc_html($disp_email); ?></strong>
                                    <?php if (!empty($lead->phone)) : ?>
                                        <br><span style="font-size:11.5px;color:#059669;"><?php echo esc_html($lead->phone); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($prod_name); ?></strong>
                                    <?php if ($src_url) : ?>
                                        <br><a href="<?php echo esc_url($src_url); ?>" target="_blank" style="font-size:11px;color:#0284c7;">查看来源页面 &rarr;</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (is_array($answers) && count($answers) > 0) : ?>
                                        <ul style="margin:0;padding-left:14px;list-style:disc;font-size:12px;color:#1e293b;">
                                            <?php foreach ($answers as $fq => $fa) : ?>
                                                <li><strong><?php echo esc_html($fq); ?>:</strong> <?php echo esc_html(is_array($fa) ? implode(', ', $fa) : $fa); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else : ?>
                                        <span style="color:#94a3b8;font-size:12px;">无其他附加参数</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($country); ?><br>
                                    <?php if ($dwell > 0) : ?>
                                        <span style="font-size:11px;color:#16a34a;">⏱️ <?php echo $dwell; ?>s dwell</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lead->status === 'new') : ?>
                                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">NEW</span>
                                    <?php else : ?>
                                        <span style="background:#f3f4f6;color:#4b5563;padding:2px 8px;border-radius:10px;font-size:11px;">Contacted</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($lead->created_at); ?></td>
                                <td>
                                    <?php if ($lead->status === 'new') : ?>
                                        <a href="<?php echo esc_url($mark_url); ?>" class="button button-small">Mark Contacted</a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($del_url); ?>" class="button button-small button-link-delete" onclick="return confirm('Delete this inquiry?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_settings_page() {
        settings_errors('wpi_messages');
        $language       = get_option('wpi_language', 'en');
        $enabled        = get_option('wpi_enabled', '1');
        $bot_name       = get_option('wpi_bot_name', 'Sales Specialist');
        $welcome_msg    = get_option('wpi_welcome_msg', 'Hi! Looking for factory-direct wholesale prices or CAD specs for "{product_name}"?');
        $default_msg    = get_option('wpi_default_welcome_msg', 'Hi there! Welcome to our official site. How can we help your project today?');
        $auto_popup     = (int) get_option('wpi_auto_popup_seconds', 15);
        $exit_intent    = (int) get_option('wpi_exit_intent', 1);
        $scroll_trigger = (int) get_option('wpi_scroll_trigger', 50);
        $success_msg    = get_option('wpi_lead_success_msg', 'Thank you! Our engineering and sales team will contact you within 2 hours with the specs and quotation.');
        $notify_email   = get_option('wpi_notify_email', get_option('admin_email'));
        $webhook_url    = get_option('wpi_webhook_url', '');
        $webhook_secret = get_option('wpi_webhook_secret', '');
        $faq_list       = get_option('wpi_faq_list', self::get_default_faqs());
        ?>
        <div class="wrap">
            <h1>Chatbot Settings (ECV Conversion Mode)</h1>
            <form method="post" action="">
                <?php wp_nonce_field('wpi_save_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Chatbot</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpi_enabled" value="1" <?php checked($enabled, '1'); ?>>
                                Display floating inquiry chatbot on frontend
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Language Configuration (语言配置)</th>
                        <td>
                            <fieldset>
                                <label style="margin-right:25px;display:inline-block;margin-bottom:6px;">
                                    <input type="radio" name="wpi_language" value="en" <?php checked($language, 'en'); ?>>
                                    <strong>English (英文模式)</strong> <span class="description">- 外贸出海独立站推荐</span>
                                </label>
                                <br>
                                <label style="margin-right:25px;display:inline-block;margin-bottom:6px;">
                                    <input type="radio" name="wpi_language" value="zh_CN" <?php checked($language, 'zh_CN'); ?>>
                                    <strong>简体中文 (中文模式)</strong> <span class="description">- 国内站、中文品牌官网推荐</span>
                                </label>
                                <br>
                                <label style="display:inline-block;">
                                    <input type="radio" name="wpi_language" value="auto" <?php checked($language, 'auto'); ?>>
                                    <strong>智能自适应 (Auto Detect)</strong> <span class="description">- 根据访客浏览器语言自动匹配中英文</span>
                                </label>
                            </fieldset>
                            <p class="description" style="margin-top:6px;">
                                控制前台浮窗状态、输入框占位符、错误校验提示的语言。
                            </p>
                            <div style="margin-top:10px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;display:inline-block;">
                                <strong style="font-size:12px;color:#334155;margin-right:8px;">快捷重置基本话术：</strong>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=load_i18n_defaults&lang=zh_CN'), 'wpi_load_i18n')); ?>" class="button button-secondary" onclick="return confirm('确定重置欢迎语、客服名称、成功提示为简体中文吗？');">
                                    一键填充中文预设话术
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=load_i18n_defaults&lang=en'), 'wpi_load_i18n')); ?>" class="button button-secondary" onclick="return confirm('Reset basic greeting, bot name, and success message to English?');" style="margin-left:6px;">
                                    一键填充英文预设话术
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Specialist / Bot Name</th>
                        <td>
                            <input type="text" name="wpi_bot_name" value="<?php echo esc_attr($bot_name); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Context-Aware Greeting</th>
                        <td>
                            <textarea name="wpi_welcome_msg" rows="2" class="large-text"><?php echo esc_textarea($welcome_msg); ?></textarea>
                            <p class="description">Used on product/article pages. Use <code>{product_name}</code> to dynamically insert the page/product title.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Home / General Greeting</th>
                        <td>
                            <textarea name="wpi_default_welcome_msg" rows="2" class="large-text"><?php echo esc_textarea($default_msg); ?></textarea>
                            <p class="description">Fallback greeting on home page or generic archive pages.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Proactive Auto-Popup (Seconds)</th>
                        <td>
                            <input type="number" name="wpi_auto_popup_seconds" value="<?php echo esc_attr($auto_popup); ?>" min="0" max="300" class="small-text"> seconds
                            <p class="description">Set to <code>15</code> to automatically open the inquiry invitation after 15 seconds on page (0 = disable).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Exit-Intent Interception</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpi_exit_intent" value="1" <?php checked($exit_intent, 1); ?>>
                                Pop open when visitor moves mouse towards closing the browser tab
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Scroll Depth Trigger (%)</th>
                        <td>
                            <input type="number" name="wpi_scroll_trigger" value="<?php echo esc_attr($scroll_trigger); ?>" min="0" max="100" class="small-text"> %
                            <p class="description">Open widget when user scrolls past this percentage of the page (0 = disable).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lead Captured Message</th>
                        <td>
                            <textarea name="wpi_lead_success_msg" rows="2" class="large-text"><?php echo esc_textarea($success_msg); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Notification Email</th>
                        <td>
                            <input type="email" name="wpi_notify_email" value="<?php echo esc_attr($notify_email); ?>" class="regular-text">
                            <p class="description">Instant email alert recipient when a new lead is submitted.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp / Phone Number</th>
                        <td>
                            <input type="text" name="wpi_whatsapp_number" value="<?php echo esc_attr(get_option('wpi_whatsapp_number', '')); ?>" class="regular-text" placeholder="+1 555 123 4567">
                            <p class="description">Official sales WhatsApp/Phone number (with country code). Triggered when visitor clicks "Chat on WhatsApp".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Feishu Webhook URL</th>
                        <td>
                            <input type="url" id="wpi_webhook_url" name="wpi_webhook_url" value="<?php echo esc_attr($webhook_url); ?>" class="large-text" placeholder="https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            <p class="description">Feishu custom robot webhook URL. Sends formatted rich interactive cards with WhatsApp & Mailto shortcuts.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Feishu Secret (签名密钥)</th>
                        <td>
                            <input type="text" id="wpi_webhook_secret" name="wpi_webhook_secret" value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text" placeholder="Optional HMAC SHA256 secret">
                            <p class="description">Optional: If signature verification (签名校验) is turned on in your Feishu robot security settings.</p>
                            <div style="margin-top:10px;">
                                <button type="button" id="wpi-test-webhook-btn" class="button button-secondary">Send Test Feishu Inquiry Ticket (发送测试工单卡片)</button>
                                <span id="wpi-test-webhook-result" style="margin-left:10px;font-weight:600;"></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">FAQ Keyword Corpus (JSON)</th>
                        <td>
                            <textarea name="wpi_faq_json" rows="8" class="large-text code"><?php echo esc_textarea(wp_json_encode($faq_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                            <p class="description">Deterministic keyword matching rules. Zero LLM latency.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">插件自动更新 (Auto Update)</th>
                        <td>
                            <p><strong>当前运行版本：</strong> <code>v<?php echo esc_html(WPI_VERSION); ?></code></p>
                            <p style="margin-top:6px;">
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpi-settings&wpi_check_update=1'), 'wpi_check_update_action')); ?>" class="button button-secondary">
                                    立即检查新版本 (Check for Updates)
                                </a>
                                <span class="description" style="margin-left:8px;">点击将强制连接 Gitea 检查是否有新版本，并跳转至 WordPress 更新中心。</span>
                            </p>
                            <details style="margin-top:10px;font-size:12px;color:#475569;">
                                <summary style="cursor:pointer;color:#0284c7;">高级更新源设置 (Advanced Update Settings)</summary>
                                <div style="margin-top:8px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;max-width:680px;">
                                    <label><strong>更新元数据地址 (Update JSON URL):</strong></label><br>
                                    <input type="url" name="wpi_update_url" value="<?php echo esc_attr(WPI_Updater::get_update_url()); ?>" class="large-text" style="margin:4px 0 8px;"><br>
                                    <label><strong>Gitea 只读访问凭证 (Access Token):</strong></label><br>
                                    <input type="text" name="wpi_update_token" value="<?php echo esc_attr(WPI_Updater::get_token()); ?>" class="regular-text" style="margin-top:4px;">
                                    <p class="description">默认已预设只读更新密钥，通常无需修改。</p>
                                </div>
                            </details>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="wpi_save_settings" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>

        <script>
        document.getElementById('wpi-test-webhook-btn')?.addEventListener('click', function () {
            const btn = this;
            const resSpan = document.getElementById('wpi-test-webhook-result');
            const webhookUrl = document.getElementById('wpi_webhook_url').value.trim();
            const secret = document.getElementById('wpi_webhook_secret').value.trim();

            if (!webhookUrl) {
                alert('Please enter a Feishu Webhook URL first.');
                return;
            }

            btn.disabled = true;
            resSpan.style.color = '#4b5563';
            resSpan.innerText = 'Sending test card to Feishu...';

            fetch('<?php echo esc_url_raw(rest_url('wpi/v1/test-webhook')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify({ webhook_url: webhookUrl, secret: secret })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    resSpan.style.color = '#16a34a';
                    resSpan.innerText = 'Test Feishu Card sent successfully! Check your Feishu group.';
                } else {
                    resSpan.style.color = '#dc2626';
                    resSpan.innerText = 'Error: ' + (data.message || 'Failed to dispatch to Feishu');
                }
            })
            .catch(err => {
                btn.disabled = false;
                resSpan.style.color = '#dc2626';
                resSpan.innerText = 'Request failed: ' + err.message;
            });
        });
        </script>
        <?php
    }
}

WPI_Admin::init();
