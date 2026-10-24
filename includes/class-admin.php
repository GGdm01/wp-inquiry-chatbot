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
            '询盘线索',
            '询盘线索' . $badge,
            'manage_options',
            'wpi-leads',
            [__CLASS__, 'render_leads_page'],
            'dashicons-format-chat',
            26
        );

        add_submenu_page(
            'wpi-leads',
            '全部工单',
            '全部工单',
            'manage_options',
            'wpi-leads',
            [__CLASS__, 'render_leads_page']
        );

        add_submenu_page(
            'wpi-leads',
            '表单与追问流程',
            '表单与语料',
            'manage_options',
            'wpi-form-builder',
            [__CLASS__, 'render_form_builder_page']
        );

        add_submenu_page(
            'wpi-leads',
            '机器人核心设置',
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
        if (isset($_GET['page']) && in_array($_GET['page'], ['wpi-form-builder', 'wpi-settings'], true) && isset($_GET['action']) && $_GET['action'] === 'export_corpus') {
            check_admin_referer('wpi_export_corpus');
            $corpus_data = [
                'bot_name'            => get_option('wpi_bot_name', 'Sales Specialist'),
                'welcome_msg'         => get_option('wpi_welcome_msg', ''),
                'default_welcome_msg' => get_option('wpi_default_welcome_msg', ''),
                'lead_success_msg'    => get_option('wpi_lead_success_msg', ''),
                'form_questions'      => get_option('wpi_form_questions', self::get_default_form_questions()),
                'faq_list'            => get_option('wpi_faq_list', self::get_default_faqs()),
                'exported_at'         => current_time('mysql'),
                'version'             => WPI_VERSION,
            ];

            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=wp-chatbot-corpus-' . date('Y-m-d') . '.json');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo wp_json_encode($corpus_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Handle Save Current Corpus as Custom Default Snapshot
        if (isset($_GET['page']) && in_array($_GET['page'], ['wpi-form-builder', 'wpi-settings'], true) && isset($_GET['action']) && $_GET['action'] === 'save_custom_default') {
            check_admin_referer('wpi_custom_default_action');
            $redirect_page = sanitize_key($_GET['page']);

            update_option('wpi_custom_default_questions', get_option('wpi_form_questions', self::get_default_form_questions()));
            update_option('wpi_custom_default_faqs', get_option('wpi_faq_list', self::get_default_faqs()));
            update_option('wpi_custom_default_welcome_msg', get_option('wpi_welcome_msg', ''));
            update_option('wpi_custom_default_default_msg', get_option('wpi_default_welcome_msg', ''));
            update_option('wpi_custom_default_bot_name', get_option('wpi_bot_name', 'Sales Specialist'));
            update_option('wpi_custom_default_success_msg', get_option('wpi_lead_success_msg', ''));
            update_option('wpi_custom_default_saved_at', current_time('mysql'));

            add_settings_error('wpi_messages', 'wpi_custom_saved', '已成功将当前调整好的追问表单流程与 FAQ 规则保存为「站点专属默认预设」！后续发生误改时可随时一键恢复。', 'updated');
            wp_safe_redirect(admin_url('admin.php?page=' . $redirect_page));
            exit;
        }

        // Handle Restore Custom Default Snapshot
        if (isset($_GET['page']) && in_array($_GET['page'], ['wpi-form-builder', 'wpi-settings'], true) && isset($_GET['action']) && $_GET['action'] === 'restore_custom_default') {
            check_admin_referer('wpi_custom_default_action');
            $redirect_page = sanitize_key($_GET['page']);
            $saved_questions = get_option('wpi_custom_default_questions', null);
            $saved_at        = get_option('wpi_custom_default_saved_at', '');

            if ($saved_questions === null) {
                add_settings_error('wpi_messages', 'wpi_custom_error', '尚未保存过「站点专属默认预设」，请先在表单中调整好后点击保存！', 'error');
            } else {
                update_option('wpi_form_questions', $saved_questions);
                $saved_faqs = get_option('wpi_custom_default_faqs', null);
                if ($saved_faqs !== null) {
                    update_option('wpi_faq_list', $saved_faqs);
                }
                $saved_welcome = get_option('wpi_custom_default_welcome_msg', null);
                if ($saved_welcome !== null) {
                    update_option('wpi_welcome_msg', $saved_welcome);
                }
                $saved_default = get_option('wpi_custom_default_default_msg', null);
                if ($saved_default !== null) {
                    update_option('wpi_default_welcome_msg', $saved_default);
                }
                $saved_bot_name = get_option('wpi_custom_default_bot_name', null);
                if ($saved_bot_name !== null) {
                    update_option('wpi_bot_name', $saved_bot_name);
                }
                $saved_success = get_option('wpi_custom_default_success_msg', null);
                if ($saved_success !== null) {
                    update_option('wpi_lead_success_msg', $saved_success);
                }

                add_settings_error('wpi_messages', 'wpi_custom_restored', sprintf('已成功恢复到于 %s 保存的站点专属默认预设！', esc_html($saved_at)), 'updated');
            }

            wp_safe_redirect(admin_url('admin.php?page=' . $redirect_page));
            exit;
        }

        // Handle Preset Switching
        if (isset($_GET['page']) && in_array($_GET['page'], ['wpi-form-builder', 'wpi-settings'], true) && isset($_GET['action']) && $_GET['action'] === 'apply_preset' && isset($_GET['preset'])) {
            check_admin_referer('wpi_apply_preset');
            $preset = sanitize_key($_GET['preset']);
            $redirect_page = sanitize_key($_GET['page']);

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

            wp_safe_redirect(admin_url('admin.php?page=' . $redirect_page));
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
                add_settings_error('wpi_messages', 'wpi_i18n_loaded', '已切换为英文模式，并成功重置为英文预设问候语与客服名称。', 'updated');
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

            if (isset($_POST['visual_questions']) && is_array($_POST['visual_questions'])) {
                $cleaned_questions = [];
                foreach ($_POST['visual_questions'] as $q) {
                    $id = sanitize_key($q['id'] ?? '');
                    if (empty($id)) {
                        continue;
                    }
                    $options_raw = $q['options'] ?? '';
                    $options = [];
                    if (is_array($options_raw)) {
                        $options = array_map('sanitize_text_field', $options_raw);
                    } elseif (is_string($options_raw) && trim($options_raw) !== '') {
                        $parts = preg_split('/[,，\n\r]+/', $options_raw);
                        foreach ($parts as $p) {
                            $p = trim($p);
                            if ($p !== '') {
                                $options[] = sanitize_text_field($p);
                            }
                        }
                    }

                    $type = sanitize_key($q['type'] ?? 'choice_or_text');
                    $require_at = !empty($q['require_at_email']) || $type === 'email' || $type === 'name_email';

                    $cleaned_questions[] = [
                        'id'               => $id,
                        'question'         => sanitize_textarea_field($q['question'] ?? ''),
                        'type'             => $type,
                        'options'          => $options,
                        'required'         => !empty($q['required']) || $require_at,
                        'require_at_email' => $require_at,
                        'prompt_error'     => sanitize_text_field($q['prompt_error'] ?? ''),
                        'placeholder'      => sanitize_text_field($q['placeholder'] ?? ''),
                    ];
                }
                if (!empty($cleaned_questions)) {
                    update_option('wpi_form_questions', $cleaned_questions);
                }
            } elseif (!empty($_POST['wpi_form_questions_json'])) {
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

            add_settings_error('wpi_messages', 'wpi_form_saved', '已成功保存追问表单流程与语料配置！', 'updated');
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
            if (isset($_POST['visual_faqs']) && is_array($_POST['visual_faqs'])) {
                $cleaned_faqs = [];
                foreach ($_POST['visual_faqs'] as $faq) {
                    $kw_raw = $faq['keywords'] ?? '';
                    $keywords = [];
                    if (is_array($kw_raw)) {
                        $keywords = array_map('sanitize_text_field', $kw_raw);
                    } elseif (is_string($kw_raw) && trim($kw_raw) !== '') {
                        $parts = preg_split('/[,，\n\r]+/', $kw_raw);
                        foreach ($parts as $p) {
                            $p = trim($p);
                            if ($p !== '') {
                                $keywords[] = sanitize_text_field($p);
                            }
                        }
                    }
                    $answer = sanitize_textarea_field($faq['answer'] ?? '');
                    if (!empty($keywords) && !empty($answer)) {
                        $cleaned_faqs[] = [
                            'keywords' => $keywords,
                            'answer'   => $answer,
                        ];
                    }
                }
                update_option('wpi_faq_list', $cleaned_faqs);
            } elseif (!empty($_POST['wpi_faq_json'])) {
                $decoded_faq = json_decode(stripslashes($_POST['wpi_faq_json']), true);
                if (is_array($decoded_faq)) {
                    update_option('wpi_faq_list', $decoded_faq);
                }
            }

            add_settings_error('wpi_messages', 'wpi_saved', '已成功保存机器人核心配置！', 'updated');
        }

        // Handle Corpus JSON Import from Settings Page
        if (isset($_POST['wpi_import_corpus_settings'])) {
            check_admin_referer('wpi_save_settings_nonce');
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
                    if (!empty($imported['default_welcome_msg'])) {
                        update_option('wpi_default_welcome_msg', sanitize_textarea_field($imported['default_welcome_msg']));
                    }
                    if (!empty($imported['bot_name'])) {
                        update_option('wpi_bot_name', sanitize_text_field($imported['bot_name']));
                    }
                    if (!empty($imported['lead_success_msg'])) {
                        update_option('wpi_lead_success_msg', sanitize_textarea_field($imported['lead_success_msg']));
                    }
                    add_settings_error('wpi_messages', 'wpi_corpus_imported', '已成功从外部 JSON 备份文件恢复整套语料库与表单流程！', 'updated');
                } else {
                    add_settings_error('wpi_messages', 'wpi_corpus_import_err', '导入失败：JSON 格式无效，请检查复制的内容。', 'error');
                }
            }
        }
    }

    /**
     * Custom Form & Question Builder Page (交互式可视化表单流程编辑器)
     */
    public static function render_form_builder_page() {
        settings_errors('wpi_messages');
        $form_questions = get_option('wpi_form_questions', self::get_default_form_questions());
        $export_url = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=export_corpus'), 'wpi_export_corpus');
        $save_custom_default_url = wp_nonce_url(admin_url('admin.php?page=wpi-form-builder&action=save_custom_default'), 'wpi_custom_default_action');
        ?>
        <div class="wrap wpi-admin-wrap">
            <h1 class="wp-heading-inline">自定义表单与追问流程</h1>
            <a href="<?php echo esc_url($export_url); ?>" class="page-title-action" style="font-weight:600;">导出完整语料 JSON</a>
            <a href="<?php echo esc_url($save_custom_default_url); ?>" class="page-title-action" style="font-weight:600;color:#0369a1;" onclick="return confirm('确定将当前调整好的追问表单流程与问答规则保存为专属默认预设吗？');">💾 将当前调整好的设置保存为默认预设</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpi-settings#wpi-backup-section')); ?>" class="page-title-action" style="font-weight:600;color:#64748b;">语料预设与快照恢复 &rarr;</a>
            <hr class="wp-header-end">

            <p style="font-size:14px;color:#475569;margin:12px 0 16px;">
                可视化配置询盘机器人的多轮发散追问流（Probing Question Flow）。支持即时调整提问话术、快捷选项（Chips）、必填强校验规则及步骤顺序。如需切换出厂预设或备份快照，请前往“<a href="<?php echo esc_url(admin_url('admin.php?page=wpi-settings#wpi-backup-section')); ?>">语料预设与快照恢复</a>”。
            </p>
            <form method="post" action="" id="wpi-form-builder-form">
                <?php wp_nonce_field('wpi_save_form_nonce'); ?>

                <div style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
                    <h2 style="margin:0;font-size:17px;color:#1e293b;">追问步骤流程列表 (<span id="wpi-steps-total-count"><?php echo count($form_questions); ?></span> 步)</h2>
                    <span class="description" style="font-size:13px;color:#64748b;">支持通过右侧按钮上移、下移调整客户答题顺序</span>
                </div>

                <!-- 可视化步骤卡片列表容器 -->
                <div id="wpi-questions-list">
                    <?php foreach ($form_questions as $index => $q) :
                        $q_type = $q['type'] ?? 'choice_or_text';
                        $q_options = is_array($q['options'] ?? null) ? implode(', ', $q['options']) : ($q['options'] ?? '');
                        $is_strict_email = !empty($q['require_at_email']) || $q_type === 'email' || $q_type === 'name_email';
                    ?>
                        <div class="wpi-question-card" data-index="<?php echo $index; ?>" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:16px 20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f1f5f9;">
                                <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
                                    <span class="wpi-step-badge" style="background:#0284c7;color:#ffffff;font-size:12px;font-weight:700;padding:2px 10px;border-radius:12px;white-space:nowrap;">
                                        步骤 #<span class="wpi-step-num"><?php echo $index + 1; ?></span>
                                    </span>
                                    <strong class="wpi-q-summary" style="font-size:14px;color:#1e293b;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;">
                                        <?php echo esc_html(wp_strip_all_tags($q['question'] ?: ($q['id'] ?? '未命名追问'))); ?>
                                    </strong>
                                </div>
                                <div class="wpi-q-actions" style="display:flex;gap:6px;flex-shrink:0;">
                                    <button type="button" class="button button-small wpi-btn-up" title="上移此题" <?php echo $index === 0 ? 'disabled' : ''; ?>>↑ 上移</button>
                                    <button type="button" class="button button-small wpi-btn-down" title="下移此题" <?php echo $index === count($form_questions) - 1 ? 'disabled' : ''; ?>>↓ 下移</button>
                                    <button type="button" class="button button-small button-link-delete wpi-btn-del" title="删除此题">✕ 删除</button>
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                                <div>
                                    <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">步骤字段标识 (ID)：</label>
                                    <input type="text" name="visual_questions[<?php echo $index; ?>][id]" value="<?php echo esc_attr($q['id'] ?? ''); ?>" class="regular-text wpi-q-id-input" style="width:100%;" placeholder="例如：email, whatsapp_contact, screen_size" required>
                                    <span class="description" style="font-size:12px;">全局唯一英文键名，如 <code>email</code>、<code>whatsapp_contact</code></span>
                                </div>
                                <div>
                                    <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">提问交互类型 (Type)：</label>
                                    <select name="visual_questions[<?php echo $index; ?>][type]" class="wpi-q-type-select" style="width:100%;">
                                        <option value="email" <?php selected($q_type, 'email'); ?>>📧 电子邮箱录入 (强校验包含 @ 符号)</option>
                                        <option value="choice" <?php selected($q_type, 'choice'); ?>>🔘 纯快捷选项卡片 (点击即自动下一步)</option>
                                        <option value="choice_or_text" <?php selected($q_type, 'choice_or_text'); ?>>💬 快捷选项卡片 或 自由文本输入</option>
                                        <option value="optional_text" <?php selected($q_type, 'optional_text'); ?>>📝 选填自由文本 (允许访客跳过)</option>
                                        <option value="phone" <?php selected($q_type, 'phone'); ?>>📱 电话 / WhatsApp 录入</option>
                                        <option value="text" <?php selected($q_type, 'text'); ?>>✏️ 普通必填文本</option>
                                        <option value="name_email" <?php selected($q_type, 'name_email'); ?>>👤 姓名与邮箱组合录入</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-bottom:12px;">
                                <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">机器人在该步骤提出的问题 (Question Prompt)：</label>
                                <textarea name="visual_questions[<?php echo $index; ?>][question]" rows="2" class="large-text wpi-q-text-input" style="width:100%;" placeholder="输入机器人问询客户的完整句子..."><?php echo esc_textarea($q['question'] ?? ''); ?></textarea>
                                <span class="description" style="font-size:12px;">支持使用 <code>{product_name}</code> 变量动态插入访客当前正在浏览的产品名称。</span>
                            </div>

                            <div class="wpi-options-row" style="margin-bottom:12px;">
                                <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">快捷选项卡片按钮 (Options Chips)：</label>
                                <input type="text" name="visual_questions[<?php echo $index; ?>][options]" value="<?php echo esc_attr($q_options); ?>" class="large-text" style="width:100%;" placeholder="例如：Chat on WhatsApp, Prefer Email only">
                                <span class="description" style="font-size:12px;">多个选项请用<strong>英文逗号</strong>隔开。前台将渲染为可点击的气泡选项卡片（如：<code>Chat on WhatsApp, Prefer Email only</code>）。</span>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                                <div>
                                    <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">输入框占位提示 (Placeholder)：</label>
                                    <input type="text" name="visual_questions[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr($q['placeholder'] ?? ''); ?>" class="regular-text" style="width:100%;" placeholder="例如：Enter your business Email (name@company.com)...">
                                </div>
                                <div>
                                    <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">校验失败引导提示 (Prompt Error)：</label>
                                    <input type="text" name="visual_questions[<?php echo $index; ?>][prompt_error]" value="<?php echo esc_attr($q['prompt_error'] ?? ''); ?>" class="regular-text" style="width:100%;" placeholder="格式错误或缺失邮箱时机器人的报错引导语...">
                                </div>
                            </div>

                            <div style="padding-top:10px;border-top:1px dashed #e2e8f0;display:flex;gap:24px;align-items:center;">
                                <label style="font-weight:600;color:#991b1b;display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                                    <input type="checkbox" name="visual_questions[<?php echo $index; ?>][require_at_email]" value="1" <?php checked($is_strict_email); ?>>
                                    强制要求输入包含“@”的有效商业邮箱 (无 @ 自动拦截重输)
                                </label>
                                <label style="color:#475569;display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                                    <input type="checkbox" name="visual_questions[<?php echo $index; ?>][required]" value="1" <?php checked(!empty($q['required']) || $is_strict_email); ?>>
                                    必填项 (不允许留空或直接跳过)
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 添加新追问步骤按钮 -->
                <button type="button" id="wpi-add-step-btn" class="button button-large button-secondary" style="width:100%;padding:10px 0;text-align:center;border-style:dashed;margin:12px 0 20px;font-weight:600;color:#0284c7;background:#f8fafc;">
                    + 添加新追问步骤 (Add Question Step)
                </button>

                <p class="submit" style="margin-top:0;">
                    <button type="submit" name="wpi_save_form_builder" class="button button-primary button-large" style="padding:4px 28px;font-size:14px;font-weight:600;">
                        保存追问表单流程设置
                    </button>
                </p>

                <!-- 高级开发者模式折叠抽屉 -->
                <details style="margin-top:28px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 18px;">
                    <summary style="cursor:pointer;font-weight:600;color:#0284c7;font-size:13px;">高级开发者模式：JSON 源码查看与备份导入 (点击展开)</summary>
                    <div style="margin-top:12px;">
                        <h4 style="margin:8px 0 4px;font-size:13px;color:#334155;">当前追问语料 JSON 源码 (导出/查看)：</h4>
                        <textarea name="wpi_form_questions_json" rows="10" class="large-text code"><?php echo esc_textarea(wp_json_encode($form_questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                    </div>
                    <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #cbd5e1;">
                        <h4 style="margin:8px 0 4px;font-size:13px;color:#334155;">语料库一键导入 (Import Corpus JSON)：</h4>
                        <p class="description" style="margin-bottom:6px;">粘贴外部导出的问答语料库 JSON（包含问卷流、FAQ、开场白），提交时将覆盖导入：</p>
                        <textarea name="wpi_import_corpus_json" rows="4" class="large-text code" placeholder='{"form_questions": [...], "faq_list": [...]}'></textarea>
                    </div>
                </details>
            </form>
        </div>

        <script>
        (function() {
            const list = document.getElementById('wpi-questions-list');
            const addBtn = document.getElementById('wpi-add-step-btn');
            const totalCountSpan = document.getElementById('wpi-steps-total-count');

            function updateCardIndices() {
                if (!list) return;
                const cards = list.querySelectorAll('.wpi-question-card');
                if (totalCountSpan) totalCountSpan.innerText = cards.length;
                cards.forEach((card, idx) => {
                    card.dataset.index = idx;
                    const badgeNum = card.querySelector('.wpi-step-num');
                    if (badgeNum) badgeNum.innerText = idx + 1;

                    const upBtn = card.querySelector('.wpi-btn-up');
                    const downBtn = card.querySelector('.wpi-btn-down');
                    if (upBtn) upBtn.disabled = (idx === 0);
                    if (downBtn) downBtn.disabled = (idx === cards.length - 1);

                    // Re-index all name attributes inside card
                    card.querySelectorAll('input, select, textarea').forEach(input => {
                        if (input.name && input.name.startsWith('visual_questions[')) {
                            input.name = input.name.replace(/^visual_questions\[\d+\]/, 'visual_questions[' + idx + ']');
                        }
                    });
                });
            }

            if (list) {
                // Live update summary header on question input
                list.addEventListener('input', function(e) {
                    if (e.target.classList.contains('wpi-q-text-input') || e.target.classList.contains('wpi-q-id-input')) {
                        const card = e.target.closest('.wpi-question-card');
                        if (card) {
                            const textInput = card.querySelector('.wpi-q-text-input');
                            const idInput = card.querySelector('.wpi-q-id-input');
                            const summary = card.querySelector('.wpi-q-summary');
                            if (summary) {
                                const text = (textInput && textInput.value.trim()) || (idInput && idInput.value.trim()) || '未命名追问步骤';
                                summary.innerText = text;
                            }
                        }
                    }
                });

                // Move Up / Move Down / Delete Delegation
                list.addEventListener('click', function(e) {
                    const btn = e.target.closest('button');
                    if (!btn) return;
                    const card = btn.closest('.wpi-question-card');
                    if (!card) return;

                    if (btn.classList.contains('wpi-btn-up')) {
                        const prev = card.previousElementSibling;
                        if (prev && prev.classList.contains('wpi-question-card')) {
                            list.insertBefore(card, prev);
                            updateCardIndices();
                        }
                    } else if (btn.classList.contains('wpi-btn-down')) {
                        const next = card.nextElementSibling;
                        if (next && next.classList.contains('wpi-question-card')) {
                            list.insertBefore(next, card);
                            updateCardIndices();
                        }
                    } else if (btn.classList.contains('wpi-btn-del')) {
                        if (list.querySelectorAll('.wpi-question-card').length <= 1) {
                            alert('至少需要保留一个追问步骤。');
                            return;
                        }
                        if (confirm('确定删除此追问步骤吗？')) {
                            card.remove();
                            updateCardIndices();
                        }
                    }
                });
            }

            // Add new question step
            if (addBtn && list) {
                addBtn.addEventListener('click', function() {
                    const cards = list.querySelectorAll('.wpi-question-card');
                    const newIndex = cards.length;
                    const newCard = document.createElement('div');
                    newCard.className = 'wpi-question-card';
                    newCard.dataset.index = newIndex;
                    newCard.style.cssText = 'background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:16px 20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.04);';
                    newCard.innerHTML = `
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f1f5f9;">
                            <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
                                <span class="wpi-step-badge" style="background:#0284c7;color:#ffffff;font-size:12px;font-weight:700;padding:2px 10px;border-radius:12px;white-space:nowrap;">
                                    步骤 #<span class="wpi-step-num">${newIndex + 1}</span>
                                </span>
                                <strong class="wpi-q-summary" style="font-size:14px;color:#1e293b;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;">
                                    新追问步骤
                                </strong>
                            </div>
                            <div class="wpi-q-actions" style="display:flex;gap:6px;flex-shrink:0;">
                                <button type="button" class="button button-small wpi-btn-up" title="上移此题">↑ 上移</button>
                                <button type="button" class="button button-small wpi-btn-down" title="下移此题" disabled>↓ 下移</button>
                                <button type="button" class="button button-small button-link-delete wpi-btn-del" title="删除此题">✕ 删除</button>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                            <div>
                                <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">步骤字段标识 (ID)：</label>
                                <input type="text" name="visual_questions[${newIndex}][id]" value="custom_step_${newIndex + 1}" class="regular-text wpi-q-id-input" style="width:100%;" required>
                                <span class="description" style="font-size:12px;">唯一英文键名，如 <code>screen_size</code>、<code>quantity</code></span>
                            </div>
                            <div>
                                <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">提问交互类型 (Type)：</label>
                                <select name="visual_questions[${newIndex}][type]" class="wpi-q-type-select" style="width:100%;">
                                    <option value="choice_or_text" selected>💬 快捷选项卡片 或 自由文本输入</option>
                                    <option value="choice">🔘 纯快捷选项卡片 (点击即自动下一步)</option>
                                    <option value="optional_text">📝 选填自由文本 (允许访客跳过)</option>
                                    <option value="email">📧 电子邮箱录入 (强校验包含 @ 符号)</option>
                                    <option value="phone">📱 电话 / WhatsApp 录入</option>
                                    <option value="text">✏️ 普通必填文本</option>
                                    <option value="name_email">👤 姓名与邮箱组合录入</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-bottom:12px;">
                            <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">机器人在该步骤提出的问题 (Question Prompt)：</label>
                            <textarea name="visual_questions[${newIndex}][question]" rows="2" class="large-text wpi-q-text-input" style="width:100%;" placeholder="输入机器人问询客户的完整句子..."></textarea>
                            <span class="description" style="font-size:12px;">支持使用 <code>{product_name}</code> 变量动态插入访客当前正在浏览的产品名称。</span>
                        </div>

                        <div class="wpi-options-row" style="margin-bottom:12px;">
                            <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">快捷选项卡片按钮 (Options Chips)：</label>
                            <input type="text" name="visual_questions[${newIndex}][options]" value="" class="large-text" style="width:100%;" placeholder="例如：Option A, Option B, Option C (用英文逗号分隔)">
                            <span class="description" style="font-size:12px;">多个选项请用<strong>英文逗号</strong>隔开。前台将渲染为可点击的气泡选项卡片。</span>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                            <div>
                                <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">输入框占位提示 (Placeholder)：</label>
                                <input type="text" name="visual_questions[${newIndex}][placeholder]" value="Type your answer or select above..." class="regular-text" style="width:100%;">
                            </div>
                            <div>
                                <label style="font-weight:600;display:block;margin-bottom:4px;color:#334155;font-size:13px;">校验失败引导提示 (Prompt Error)：</label>
                                <input type="text" name="visual_questions[${newIndex}][prompt_error]" value="" class="regular-text" style="width:100%;">
                            </div>
                        </div>

                        <div style="padding-top:10px;border-top:1px dashed #e2e8f0;display:flex;gap:24px;align-items:center;">
                            <label style="font-weight:600;color:#991b1b;display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                                <input type="checkbox" name="visual_questions[${newIndex}][require_at_email]" value="1">
                                强制要求输入包含“@”的有效商业邮箱 (无 @ 自动拦截重输)
                            </label>
                            <label style="color:#475569;display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                                <input type="checkbox" name="visual_questions[${newIndex}][required]" value="1">
                                必填项 (不允许留空或直接跳过)
                            </label>
                        </div>
                    `;
                    list.appendChild(newCard);
                    updateCardIndices();
                    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const textInput = newCard.querySelector('.wpi-q-text-input');
                    if (textInput) textInput.focus();
                });
            }

            updateCardIndices();
        })();
        </script>
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
            <h1 class="wp-heading-inline">询盘线索与工单列表</h1>
            <a href="<?php echo esc_url($export_csv_url); ?>" class="page-title-action" style="font-weight:600;color:#0369a1;background:#e0f2fe;border-color:#bae6fd;">导出 Excel / CSV 表单</a>
            <a href="<?php echo esc_url($export_json_url); ?>" class="page-title-action" style="font-weight:600;color:#334155;">导出 JSON 结构数据</a>
            <hr class="wp-header-end">

            <ul class="subsubsub">
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wpi-leads')); ?>" class="<?php echo $status_filter === '' ? 'current' : ''; ?>">全部线索 <span class="count">(<?php echo $total_count; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wpi-leads&status=new')); ?>" class="<?php echo $status_filter === 'new' ? 'current' : ''; ?>">待跟进 <span class="count">(<?php echo $new_count; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wpi-leads&status=contacted')); ?>" class="<?php echo $status_filter === 'contacted' ? 'current' : ''; ?>">已联系 <span class="count">(<?php echo $total_count - $new_count; ?>)</span></a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped" style="margin-top:12px;">
                <thead>
                    <tr>
                        <th style="width:50px;">序号</th>
                        <th style="width:85px;">意向评级</th>
                        <th style="width:200px;">客户联系方式</th>
                        <th>咨询产品</th>
                        <th>追问回答与需求明细</th>
                        <th style="width:130px;">访客地域与停留</th>
                        <th style="width:80px;">跟进状态</th>
                        <th style="width:140px;">提交时间</th>
                        <th style="width:140px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)) : ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:24px;color:#64748b;">暂无捕获到的询盘线索。可以在网站前台打开聊天窗口进行测试！</td>
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
                                        <span style="font-size:11px;color:#16a34a;">⏱️ <?php echo $dwell; ?> 秒停留</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lead->status === 'new') : ?>
                                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">待跟进</span>
                                    <?php else : ?>
                                        <span style="background:#f3f4f6;color:#4b5563;padding:2px 8px;border-radius:10px;font-size:11px;">已联系</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($lead->created_at); ?></td>
                                <td>
                                    <?php if ($lead->status === 'new') : ?>
                                        <a href="<?php echo esc_url($mark_url); ?>" class="button button-small">标记已联系</a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($del_url); ?>" class="button button-small button-link-delete" onclick="return confirm('确定要删除此条询盘记录吗？');">删除</a>
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
        $custom_default_saved_at = get_option('wpi_custom_default_saved_at', '');
        $save_custom_default_url = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=save_custom_default'), 'wpi_custom_default_action');
        $restore_custom_default_url = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=restore_custom_default'), 'wpi_custom_default_action');
        $export_corpus_url = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=export_corpus'), 'wpi_export_corpus');

        $email_first_url    = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=apply_preset&preset=email_first'), 'wpi_apply_preset');
        $probing_url        = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=apply_preset&preset=probing'), 'wpi_apply_preset');
        $zh_email_first_url = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=apply_preset&preset=zh_email_first'), 'wpi_apply_preset');
        $zh_probing_url     = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=apply_preset&preset=zh_probing'), 'wpi_apply_preset');
        $zh_faqs_url        = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=apply_preset&preset=zh_faqs'), 'wpi_apply_preset');
        $en_faqs_url        = wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=apply_preset&preset=en_faqs'), 'wpi_apply_preset');
        ?>
        <div class="wrap wpi-admin-wrap">
            <h1>询盘机器人核心设置</h1>
            <form method="post" action="">
                <?php wp_nonce_field('wpi_save_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">启用询盘机器人</th>
                        <td>
                            <label style="font-weight:600;">
                                <input type="checkbox" name="wpi_enabled" value="1" <?php checked($enabled, '1'); ?>>
                                在网站前台右下角显示浮动询盘聊天窗口
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">前台机器人语言模式</th>
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
                                <strong style="font-size:12px;color:#334155;margin-right:8px;">快捷重置基础话术：</strong>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=load_i18n_defaults&lang=zh_CN'), 'wpi_load_i18n')); ?>" class="button button-secondary" onclick="return confirm('确定重置欢迎语、客服名称、成功提示为简体中文吗？');">
                                    一键填充中文预设话术
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpi-settings&action=load_i18n_defaults&lang=en'), 'wpi_load_i18n')); ?>" class="button button-secondary" onclick="return confirm('确定重置欢迎语、客服名称、成功提示为英文吗？');" style="margin-left:6px;">
                                    一键填充英文预设话术
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">在线客服 / 顾问名称</th>
                        <td>
                            <input type="text" name="wpi_bot_name" value="<?php echo esc_attr($bot_name); ?>" class="regular-text" placeholder="例如：Sales Specialist, 业务顾问">
                            <p class="description">前台聊天窗口顶部显示的客服人员或专家称呼。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">产品页针对性开场白</th>
                        <td>
                            <textarea name="wpi_welcome_msg" rows="2" class="large-text"><?php echo esc_textarea($welcome_msg); ?></textarea>
                            <p class="description">在产品或文章详情页生效。支持使用 <code>{product_name}</code> 动态插入当前产品或页面标题。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">首页 / 通用页面欢迎语</th>
                        <td>
                            <textarea name="wpi_default_welcome_msg" rows="2" class="large-text"><?php echo esc_textarea($default_msg); ?></textarea>
                            <p class="description">在首页、分类归档页等无具体产品上下文时的兜底问候语。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">主动弹出延时 (秒)</th>
                        <td>
                            <input type="number" name="wpi_auto_popup_seconds" value="<?php echo esc_attr($auto_popup); ?>" min="0" max="300" class="small-text"> 秒
                            <p class="description">访客停留超过指定秒数后自动展开对话框（如设为 <code>15</code>；设为 0 为禁用）。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">离开意图挽留弹窗 (Exit Intent)</th>
                        <td>
                            <label style="font-weight:600;">
                                <input type="checkbox" name="wpi_exit_intent" value="1" <?php checked($exit_intent, 1); ?>>
                                当检测到访客鼠标向浏览器顶部关闭按钮移动时，自动弹出挽留对话框
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">页面滚动深度触发 (%)</th>
                        <td>
                            <input type="number" name="wpi_scroll_trigger" value="<?php echo esc_attr($scroll_trigger); ?>" min="0" max="100" class="small-text"> %
                            <p class="description">访客向下浏览滚动超过页面指定百分比时自动弹出（如设为 <code>50</code>；设为 0 为禁用）。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">询盘提交成功感谢语</th>
                        <td>
                            <textarea name="wpi_lead_success_msg" rows="2" class="large-text"><?php echo esc_textarea($success_msg); ?></textarea>
                            <p class="description">客户完成留资提交后机器人回复的确认信息与回复承诺。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">通知接收邮箱</th>
                        <td>
                            <input type="email" name="wpi_notify_email" value="<?php echo esc_attr($notify_email); ?>" class="regular-text" placeholder="sales@yourcompany.com">
                            <p class="description">当有新客户提交询盘时，立即接收即时邮件提醒的管理员/销售邮箱。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">官方 WhatsApp / 客服电话</th>
                        <td>
                            <input type="text" name="wpi_whatsapp_number" value="<?php echo esc_attr(get_option('wpi_whatsapp_number', '')); ?>" class="regular-text" placeholder="+86 186 1234 5678">
                            <p class="description">官方销售 WhatsApp 或手机号（需包含国际区号，如 <code>+86 186...</code> 或海外号码）。当访客点击“Chat on WhatsApp”选项时自动唤起 WhatsApp 并预填当前产品名与链接。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">飞书机器人 Webhook 地址</th>
                        <td>
                            <input type="url" id="wpi_webhook_url" name="wpi_webhook_url" value="<?php echo esc_attr($webhook_url); ?>" class="large-text" placeholder="https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            <p class="description">飞书自定义机器人 Webhook 地址。新询盘秒级推送单屏高转化交互卡片。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">飞书签名校验密钥 (Secret)</th>
                        <td>
                            <input type="text" id="wpi_webhook_secret" name="wpi_webhook_secret" value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text" placeholder="选填：HMAC SHA256 签名密钥">
                            <p class="description">选填：若飞书机器人开启了安全设置中的“签名校验”，请在此输入密钥。</p>
                            <div style="margin-top:10px;">
                                <button type="button" id="wpi-test-webhook-btn" class="button button-secondary">发送测试工单卡片至飞书群</button>
                                <span id="wpi-test-webhook-result" style="margin-left:10px;font-weight:600;"></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">FAQ 常见问答语料库 (零延迟本地匹配)</th>
                        <td>
                            <p class="description" style="margin-bottom:10px;">无需调用大模型 API，毫秒级本地关键词命中与即时回复。回复后自动引导客户继续询盘流程。</p>

                            <!-- 可视化 FAQ 列表 -->
                            <div id="wpi-faq-builder" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:16px 18px;max-width:880px;">
                                <div id="wpi-faq-items">
                                    <?php foreach ($faq_list as $f_idx => $faq) :
                                        $kw_str = is_array($faq['keywords'] ?? null) ? implode(', ', $faq['keywords']) : ($faq['keywords'] ?? '');
                                    ?>
                                        <div class="wpi-faq-card" data-faq-index="<?php echo $f_idx; ?>" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px 16px;margin-bottom:12px;">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                                <strong style="color:#0369a1;font-size:13px;">问答规则 #<span class="wpi-faq-num"><?php echo $f_idx + 1; ?></span></strong>
                                                <button type="button" class="button button-small button-link-delete wpi-btn-del-faq">✕ 删除此规则</button>
                                            </div>
                                            <div style="margin-bottom:8px;">
                                                <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:3px;">匹配关键词 (Keywords)：</label>
                                                <input type="text" name="visual_faqs[<?php echo $f_idx; ?>][keywords]" value="<?php echo esc_attr($kw_str); ?>" class="large-text" style="width:100%;" placeholder="例如：moq, minimum, 起订量 (多个词用逗号分隔)">
                                            </div>
                                            <div>
                                                <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:3px;">机器人自动标准回复 (Answer)：</label>
                                                <textarea name="visual_faqs[<?php echo $f_idx; ?>][answer]" rows="2" class="large-text" style="width:100%;" placeholder="输入匹配到关键词后的自动回复话术..."><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="wpi-add-faq-btn" class="button button-secondary" style="margin-top:4px;font-weight:600;color:#0284c7;">+ 添加一条 FAQ 规则</button>
                            </div>

                            <details style="margin-top:10px;font-size:12px;color:#64748b;max-width:880px;">
                                <summary style="cursor:pointer;color:#0284c7;">高级模式：查看/编辑原始 FAQ JSON 源码</summary>
                                <div style="margin-top:8px;">
                                    <textarea name="wpi_faq_json" rows="6" class="large-text code"><?php echo esc_textarea(wp_json_encode($faq_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                                </div>
                            </details>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">插件自动更新 (Auto Update)</th>
                        <td>
                            <p><strong>当前运行版本：</strong> <code>v<?php echo esc_html(WPI_VERSION); ?></code></p>
                            <p style="margin-top:6px;">
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpi-settings&wpi_check_update=1'), 'wpi_check_update_action')); ?>" class="button button-secondary">
                                    立即检查新版本
                                </a>
                                <span class="description" style="margin-left:8px;">点击将强制连接更新仓库检查是否有新版本，并跳转至 WordPress 更新中心。</span>
                            </p>
                            <details style="margin-top:10px;font-size:12px;color:#475569;">
                                <summary style="cursor:pointer;color:#0284c7;">高级更新源设置 (Advanced Update Settings)</summary>
                                <div style="margin-top:8px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;max-width:680px;">
                                    <label><strong>更新元数据地址 (Update JSON URL):</strong></label><br>
                                    <input type="url" name="wpi_update_url" value="<?php echo esc_attr(WPI_Updater::get_update_url()); ?>" class="large-text" style="margin:4px 0 8px;"><br>
                                    <label><strong>只读访问凭证 (Access Token):</strong></label><br>
                                    <input type="text" name="wpi_update_token" value="<?php echo esc_attr(WPI_Updater::get_token()); ?>" class="regular-text" style="margin-top:4px;">
                                    <p class="description">默认已预设只读更新密钥，通常无需修改。</p>
                                </div>
                            </details>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="wpi_save_settings" class="button button-primary button-large" style="padding:4px 28px;font-size:14px;font-weight:600;">保存机器人设置</button>
                </p>
            </form>

            <!-- 语料预设切换、自定义默认快照与数据备份卡片 -->
            <div id="wpi-backup-section" style="margin-top:35px;background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:22px 24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <h2 style="margin-top:0;font-size:17px;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
                    语料预设切换、自定义默认快照与数据备份
                </h2>

                <!-- 1. 自定义默认预设（快照保存与一键恢复） -->
                <div style="margin-top:16px;padding:16px 18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                    <h3 style="margin:0 0 6px;font-size:14px;color:#1e293b;font-weight:700;">
                        1. 站点专属默认预设（保存当前微调好的配置，以便后续随时恢复）
                    </h3>
                    <p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.5;">
                        当您在「表单与追问流程」以及「FAQ 规则」中微调好满足自身业务的问答流后，可点击下方按钮保存为本站专属的“自定义默认预设”。后续无论日常维护、多语言测试或发生误操作，均可一键恢复回此快照。
                    </p>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <a href="<?php echo esc_url($save_custom_default_url); ?>" class="button button-secondary" onclick="return confirm('确定将当前在线的追问表单、问答规则与开场白保存为专属默认预设吗？');" style="font-weight:600;color:#0369a1;">
                            💾 将当前调整好的设置保存为默认预设
                        </a>
                        <?php if (!empty($custom_default_saved_at)) : ?>
                            <a href="<?php echo esc_url($restore_custom_default_url); ?>" class="button button-secondary" onclick="return confirm('⚠️ 警告：确定恢复到于 <?php echo esc_js($custom_default_saved_at); ?> 保存的默认预设吗？当前在线修改将被覆盖。');" style="font-weight:600;color:#b45309;">
                                🔄 恢复为自定义默认预设
                            </a>
                            <span style="font-size:12.5px;color:#16a34a;font-weight:600;">
                                ✓ 已保存快照 (<?php echo esc_html($custom_default_saved_at); ?>)
                            </span>
                        <?php else : ?>
                            <button type="button" class="button button-secondary" disabled style="color:#94a3b8;cursor:not-allowed;">
                                🔄 恢复为自定义默认预设 (尚未保存快照)
                            </button>
                            <span style="font-size:12.5px;color:#94a3b8;">
                                尚未保存过快照
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. JSON 完整备份与跨站导入恢复 -->
                <div style="margin-top:16px;padding:16px 18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                    <h3 style="margin:0 0 6px;font-size:14px;color:#1e293b;font-weight:700;">
                        2. 语料库 JSON 文件备份与跨站恢复 (Backup & Restore)
                    </h3>
                    <p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.5;">
                        支持将整套追问表单流程、FAQ 问答、开场白导出为单份 <code>.json</code> 备份文件，便于本地长期存档或导入到其他 WordPress 独立站。
                    </p>
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                        <a href="<?php echo esc_url($export_corpus_url); ?>" class="button button-secondary" style="font-weight:600;">
                            📥 导出完整语料 JSON 备份文件
                        </a>
                    </div>
                    <details style="font-size:12px;color:#475569;">
                        <summary style="cursor:pointer;color:#0284c7;font-weight:600;">从外部 JSON 备份文件导入恢复 (点击展开)</summary>
                        <form method="post" action="" style="margin-top:10px;">
                            <?php wp_nonce_field('wpi_save_settings_nonce'); ?>
                            <textarea name="wpi_import_corpus_json" rows="4" class="large-text code" placeholder='{"form_questions": [...], "faq_list": [...]}' style="margin-bottom:8px;"></textarea>
                            <button type="submit" name="wpi_import_corpus_settings" class="button button-secondary" onclick="return confirm('⚠️ 确定从粘贴的 JSON 覆盖恢复当前所有追问与 FAQ 吗？此操作无法撤销。');">
                                📤 导入并覆盖恢复
                            </button>
                        </form>
                    </details>
                </div>

                <!-- 3. 官方出厂业务问答语料预设（收纳折叠，防止误触） -->
                <div style="margin-top:16px;padding:14px 18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                    <details>
                        <summary style="cursor:pointer;font-weight:600;color:#64748b;font-size:13px;">
                            ⚙️ 官方出厂业务问答语料预设（点击展开切换，防止误触）
                        </summary>
                        <div style="margin-top:12px;padding-top:12px;border-top:1px dashed #cbd5e1;">
                            <p style="font-size:12.5px;color:#dc2626;margin:0 0 12px;font-weight:600;">
                                ⚠️ 注意：应用官方预设将会覆盖当前正在使用的在线表单与语料！如需切换，建议先在上方点击「将当前配置保存为默认预设」或「导出 JSON 备份」。
                            </p>
                            <div style="margin-bottom:10px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <strong style="font-size:13px;color:#0f172a;min-width:80px;">中文模式：</strong>
                                <a href="<?php echo esc_url($zh_email_first_url); ?>" class="button button-small" onclick="return confirm('⚠️ 警告：应用此预设将覆盖当前表单问题！确定切换为「中文 - 邮箱/微信前置」模式吗？');">启用「中文 - 邮箱/微信前置」模式 (推荐)</a>
                                <a href="<?php echo esc_url($zh_probing_url); ?>" class="button button-small" onclick="return confirm('⚠️ 警告：应用此预设将覆盖当前表单问题！确定切换为「中文 - 渐进式探询」模式吗？');">启用「中文 - 渐进式探询」模式</a>
                                <a href="<?php echo esc_url($zh_faqs_url); ?>" class="button button-small" onclick="return confirm('⚠️ 警告：将重置覆盖当前 FAQ 列表！确定加载中文 FAQ 语料吗？');">加载中文 FAQ 语料</a>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <strong style="font-size:13px;color:#0f172a;min-width:80px;">英文外贸：</strong>
                                <a href="<?php echo esc_url($email_first_url); ?>" class="button button-small" onclick="return confirm('⚠️ 警告：应用此预设将覆盖当前表单问题！确定切换为「英文 - 上来就要邮箱」模式吗？');">启用「英文 - 上来就要邮箱」模式 (推荐)</a>
                                <a href="<?php echo esc_url($probing_url); ?>" class="button button-small" onclick="return confirm('⚠️ 警告：应用此预设将覆盖当前表单问题！确定切换为「英文 - 渐进式多轮探询」模式吗？');">启用「英文 - 渐进式多轮探询」模式</a>
                                <a href="<?php echo esc_url($en_faqs_url); ?>" class="button button-small" onclick="return confirm('⚠️ 警告：将重置覆盖当前 FAQ 列表！确定加载英文 FAQ 语料吗？');">加载英文 FAQ 语料</a>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <script>
        (function() {
            // FAQ Builder Management
            const faqContainer = document.getElementById('wpi-faq-items');
            const addFaqBtn = document.getElementById('wpi-add-faq-btn');

            function updateFaqIndices() {
                if (!faqContainer) return;
                const rows = faqContainer.querySelectorAll('.wpi-faq-card');
                rows.forEach((row, idx) => {
                    row.dataset.faqIndex = idx;
                    const numSpan = row.querySelector('.wpi-faq-num');
                    if (numSpan) numSpan.innerText = idx + 1;

                    row.querySelectorAll('input, textarea').forEach(input => {
                        if (input.name && input.name.startsWith('visual_faqs[')) {
                            input.name = input.name.replace(/^visual_faqs\[\d+\]/, 'visual_faqs[' + idx + ']');
                        }
                    });
                });
            }

            if (faqContainer) {
                faqContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('wpi-btn-del-faq')) {
                        if (faqContainer.querySelectorAll('.wpi-faq-card').length <= 1) {
                            alert('至少需要保留一条 FAQ 规则。');
                            return;
                        }
                        if (confirm('确定删除此条 FAQ 规则吗？')) {
                            e.target.closest('.wpi-faq-card').remove();
                            updateFaqIndices();
                        }
                    }
                });
            }

            if (addFaqBtn && faqContainer) {
                addFaqBtn.addEventListener('click', function() {
                    const rows = faqContainer.querySelectorAll('.wpi-faq-card');
                    const newIdx = rows.length;
                    const newCard = document.createElement('div');
                    newCard.className = 'wpi-faq-card';
                    newCard.dataset.faqIndex = newIdx;
                    newCard.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px 16px;margin-bottom:12px;';
                    newCard.innerHTML = `
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <strong style="color:#0369a1;font-size:13px;">问答规则 #<span class="wpi-faq-num">${newIdx + 1}</span></strong>
                            <button type="button" class="button button-small button-link-delete wpi-btn-del-faq">✕ 删除此规则</button>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:3px;">匹配关键词 (Keywords)：</label>
                            <input type="text" name="visual_faqs[${newIdx}][keywords]" value="" class="large-text" style="width:100%;" placeholder="例如：custom, oem, 定制 (多个词用逗号分隔)">
                        </div>
                        <div>
                            <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:3px;">机器人自动标准回复 (Answer)：</label>
                            <textarea name="visual_faqs[${newIdx}][answer]" rows="2" class="large-text" style="width:100%;" placeholder="输入匹配到关键词后的自动回复话术..."></textarea>
                        </div>
                    `;
                    faqContainer.appendChild(newCard);
                    updateFaqIndices();
                    newCard.querySelector('input').focus();
                });
            }

            // Feishu Test Webhook
            const testBtn = document.getElementById('wpi-test-webhook-btn');
            if (testBtn) {
                testBtn.addEventListener('click', function () {
                    const btn = this;
                    const resSpan = document.getElementById('wpi-test-webhook-result');
                    const webhookUrl = document.getElementById('wpi_webhook_url').value.trim();
                    const secret = document.getElementById('wpi_webhook_secret').value.trim();

                    if (!webhookUrl) {
                        alert('请先输入飞书 Webhook 地址。');
                        return;
                    }

                    btn.disabled = true;
                    resSpan.style.color = '#4b5563';
                    resSpan.innerText = '正在向飞书群发送测试卡片...';

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
                            resSpan.innerText = '测试卡片发送成功！请检查飞书群消息。';
                        } else {
                            resSpan.style.color = '#dc2626';
                            resSpan.innerText = '推送失败：' + (data.message || '无法分发到飞书');
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        resSpan.style.color = '#dc2626';
                        resSpan.innerText = '请求异常：' + err.message;
                    });
                });
            }
        })();
        </script>
        <?php
    }
}

WPI_Admin::init();
