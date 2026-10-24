<?php
/**
 * WP Inquiry Chatbot - Server-Side Instant Setup CLI (极速一键配置工具)
 *
 * Usage:
 *   php bin/wpi-quick-setup.php [options]
 *
 * Options:
 *   --wp-path=<path>       WordPress 根目录路径 (默认自动从当前目录或父级向上探测)
 *   --industry=<archetype> 工业原型预设: medical_aesthetic | interactive_projection | smart_board | retail_signage | outdoor_lcd | video_wall | interactive_table | solar_charging | kiosk | touch_screen | stretched_bar | lighting | wear_parts | battery | generic
 *   --corpus=<file.json>   自定义追问表单 JSON 文件 (覆盖 industry 预设)
 *   --feishu=<url>         飞书机器人 Webhook 地址
 *   --secret=<secret>      飞书机器人签名密钥 (选填)
 *   --whatsapp=<number>    工程支持 WhatsApp 账号 (例如 +8618098949445)
 *   --bot-name=<name>      机器人显示称呼 (默认: Sales Specialist)
 *   --test-feishu          配置完成后立即向飞书群推送一张测试卡片验证网络连通性
 *   --clean-cache          自动清理整站页面与对象缓存 (WP Super Cache, LiteSpeed, Seraphinite 等)
 *   --help                 显示帮助信息
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be executed from the CLI.\n");
}

// -------------------------------------------------------------
// 1. CLI 参数解析
// -------------------------------------------------------------
$long_opts = [
    'wp-path::',
    'plugin-src::',
    'src::',
    'update',
    'industry::',
    'corpus::',
    'feishu::',
    'secret::',
    'whatsapp::',
    'bot-name::',
    'welcome::',
    'test-feishu',
    'clean-cache',
    'help',
];
$options = getopt('', $long_opts);

if (isset($options['help'])) {
    echo <<<HELP
\033[1;36m========================================================================\033[0m
\033[1;32mWP Inquiry Chatbot (ECV Style) - 服务器端极速一键配置工具\033[0m
\033[1;36m========================================================================\033[0m

\033[1;33m用法:\033[0m
  php bin/wpi-quick-setup.php [参数]

  --wp-path=<路径>       WordPress 安装根目录 (默认自动探测)
  --plugin-src=<目录>    指定本地插件代码源目录 (若指定则直接从该目录同步覆盖，秒级就位)
  --update               强制重新拉取最新版本覆盖现有插件
  --industry=<行业>      注入行业专属 7 步提问预设 (严格包含首步 @ 邮箱门禁):
                         • medical_aesthetic: 医美光电设备制造与高阶美容仪器 / RF微针抗衰 / 客制化机构设备代工
                         • smart_board   : 教学与会议智能平板一体机 / 交互白板 / IFPD (65"~98" 4K)
                         • interactive_projection: 儿童室内互动体感设备 & AR 投影 (互动砸球/AR沙滩沙桌/蹦床滑梯/体感运动)
                         • retail_signage: 零售商用 Android 广告机与数字标牌 / 连锁店促销大屏 / 商超导览屏
                         • touch_signage : 商用触控广告机 & 落地标牌 / 影院商超立式触控机 (LansenTouch / 泰语本土化)
                         • outdoor_lcd   : 户外高亮防水液晶屏 / 智慧公交站牌 / 户外广告机
                         • video_wall    : 液晶拼接屏电视墙 / 超窄边监控指挥大屏 / 拼接单元
                         • interactive_table: 智能触控茶几 / 会议触控桌 / 物体识别互动台
                         • solar_charging: 太阳能光伏手机充电站 / 智慧光伏座椅长椅 / 景区离网充电亭
                         • kiosk         : 自助终端机 / 钣金工控柜 / 商用整机
                         • touch_screen  : 工业触控屏 / 工控一体机 / 嵌入式显示器
                         • stretched_bar : 条形液晶屏 / 数字标牌 / 货架长条屏
                         • lighting      : 商业建筑照明 / 特种工业 LED
                         • wear_parts    : 工程机械挖掘机耐磨件 / 液压五金构件
                         • battery       : 储能锂电池系统 / 光伏储能一体机
                         • generic       : 工业通用外贸出海 (默认)
  --corpus=<文件.json>   自定义 7 步发散追问表单 JSON 路径 (优先于 --industry)
  --feishu=<Webhook>     飞书自定义机器人 Webhook URL
  --secret=<密钥>        飞书机器人签名密钥 (选填)
  --whatsapp=<电话>      WhatsApp / 电话 (如 +8618098949445)
  --bot-name=<称呼>      客服称呼 (默认: Sales Specialist)
  --test-feishu          配置完成后立即发送测试卡片，验证外贸群通知连通性
  --clean-cache          清空 WP Super Cache / LiteSpeed / Seraphinite 等页面缓存
  --help                 显示此帮助

\033[1;33m典型示例:\033[0m
  # 示例 1: 为自助机站一键完成插件激活、建表、Kiosk 语料注入、固化默认预设与缓存清理
  php bin/wpi-quick-setup.php --industry=kiosk --whatsapp="+8618098949445"

  # 示例 2: 绑定飞书推送并立即执行连通性测试
  php bin/wpi-quick-setup.php --industry=touch_screen --feishu="https://open.feishu.cn/open-apis/bot/v2/hook/xxx" --test-feishu

HELP;
    exit(0);
}

// -------------------------------------------------------------
// 2. 智能探测 WordPress 根目录并加载 wp-load.php
// -------------------------------------------------------------
function find_wp_load($custom_path = null) {
    if (!empty($custom_path)) {
        $custom_path = rtrim($custom_path, '/');
        if (file_exists($custom_path . '/wp-load.php')) {
            return $custom_path . '/wp-load.php';
        }
    }

    $probes = [
        getcwd(),
        getcwd() . '/www',
        getcwd() . '/public_html',
        dirname(__DIR__, 3), // If inside wp-content/plugins/wp-inquiry-chatbot/bin
        dirname(__DIR__, 2), // If inside wp-content/plugins/wp-inquiry-chatbot
        dirname(__DIR__, 4), // If deep subpath
    ];

    foreach ($probes as $dir) {
        if ($dir && is_dir($dir) && file_exists($dir . '/wp-load.php')) {
            return realpath($dir . '/wp-load.php');
        }
    }

    return null;
}

$wp_load_path = find_wp_load($options['wp-path'] ?? null);
if (!$wp_load_path) {
    echo "\033[1;31m[错误]\033[0m 未能找到 WordPress 根目录下的 wp-load.php！请使用 --wp-path=/path/to/wordpress 显式指定。\n";
    exit(1);
}

echo "\033[1;34m[1/6]\033[0m 正在载入 WordPress 核心: {$wp_load_path} ...\n";
define('WP_USE_THEMES', false);
require_once $wp_load_path;

if (!defined('ABSPATH')) {
    echo "\033[1;31m[错误]\033[0m WordPress 环境加载失败！\n";
    exit(1);
}

// -------------------------------------------------------------
// 3. 激活插件与执行数据库建表 / 迁移
// -------------------------------------------------------------
echo "\033[1;34m[2/6]\033[0m 检查插件安装状态与版本 ...\n";
$plugin_dir = WP_PLUGIN_DIR . '/wp-inquiry-chatbot';
$plugin_file = $plugin_dir . '/wp-inquiry-chatbot.php';
$plugin_rel_path = 'wp-inquiry-chatbot/wp-inquiry-chatbot.php';

// 辅助函数：从插件主文件读取版本号
function get_local_plugin_version($file) {
    if (!file_exists($file)) {
        return '0.0.0';
    }
    $content = file_get_contents($file);
    if (preg_match('/Version:\s*([0-9\.]+)/i', $content, $m)) {
        return trim($m[1]);
    }
    return '0.0.0';
}

// 辅助函数：目录递归同步
function sync_directory($src, $dest) {
    if (!is_dir($dest)) {
        @mkdir($dest, 0755, true);
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $target = $dest . '/' . $it->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($target)) {
                @mkdir($target, 0755, true);
            }
        } else {
            @copy($item->getRealPath(), $target);
        }
    }
}

// 分支 A: 用户显式指定了本地插件目录 (--plugin-src 或 --src)
$custom_src = $options['plugin-src'] ?? ($options['src'] ?? null);
if (!empty($custom_src)) {
    $custom_src = rtrim(realpath($custom_src) ?: $custom_src, '/');
    if (!is_dir($custom_src) || !file_exists($custom_src . '/wp-inquiry-chatbot.php')) {
        echo "\033[1;31m[错误]\033[0m 指定的插件源目录 {$custom_src} 不存在或不包含 wp-inquiry-chatbot.php！\n";
        exit(1);
    }
    $src_ver = get_local_plugin_version($custom_src . '/wp-inquiry-chatbot.php');
    echo "  \033[1;33m[指定本地源]\033[0m 正在从指定目录 {$custom_src} 同步插件代码 (版本: v{$src_ver}) ...\n";
    sync_directory($custom_src, $plugin_dir);
    echo "  \033[1;32m✓\033[0m 已成功从指定目录就位插件最新代码 (v{$src_ver})！\n";
} else {
    // 分支 B: 自动检测远端 (Gitea / GitHub) 最新版本号：有就更新，没有就装
    $local_installed = file_exists($plugin_file);
    $local_ver = $local_installed ? get_local_plugin_version($plugin_file) : '0.0.0';

    echo "  • 探测远端最新版本号 (GitHub / Gitea) ...\n";
    $remote_probe = @file_get_contents('https://raw.githubusercontent.com/GGdm01/wp-inquiry-chatbot/main/info.json');
    $remote_meta = $remote_probe ? json_decode($remote_probe, true) : null;
    $remote_ver = $remote_meta['version'] ?? '1.4.5';
    $zip_url = $remote_meta['download_url'] ?? 'https://github.com/GGdm01/wp-inquiry-chatbot/archive/refs/heads/main.zip';

    $need_install = !$local_installed;
    $need_update  = $local_installed && (version_compare($local_ver, $remote_ver, '<') || isset($options['update']));

    if ($need_install || $need_update) {
        $action_label = $need_install ? "未检测到插件，自动全新下载安装" : "检测到新版本 (本地 v{$local_ver} -> 远端 v{$remote_ver})，自动更新升级";
        echo "  \033[1;33m[自动维护]\033[0m {$action_label} (v{$remote_ver}) ...\n";

        $tmp_zip = sys_get_temp_dir() . '/wpi_latest_' . time() . '.zip';
        $zip_content = @file_get_contents($zip_url);
        if ($zip_content && file_put_contents($tmp_zip, $zip_content)) {
            $extracted = false;
            $extract_target = sys_get_temp_dir() . '/wpi_unpacked_' . time();
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($tmp_zip) === true) {
                    $zip->extractTo($extract_target);
                    $zip->close();
                    $extracted = true;
                }
            } else {
                @mkdir($extract_target, 0755, true);
                exec("unzip -q " . escapeshellarg($tmp_zip) . " -d " . escapeshellarg($extract_target), $u_out, $u_ret);
                if ($u_ret === 0) {
                    $extracted = true;
                }
            }
            @unlink($tmp_zip);
            if ($extracted) {
                $src_folder = $extract_target . '/wp-inquiry-chatbot-main';
                if (is_dir($src_folder)) {
                    sync_directory($src_folder, $plugin_dir);
                    exec("rm -rf " . escapeshellarg($extract_target));
                    echo "  \033[1;32m✓\033[0m 插件已成功就位最新版本 v{$remote_ver}！\n";
                }
            }
        }
        if ($need_install && !file_exists($plugin_file)) {
            echo "  • 降级通过 Git 克隆官方代码 ...\n";
            exec("git clone https://github.com/GGdm01/wp-inquiry-chatbot.git " . escapeshellarg($plugin_dir));
        }
    } else {
        echo "  \033[1;32m✓\033[0m 插件已是最新版本 (v{$local_ver})，无需重复下载。\n";
    }
}

// 激活插件
if (!is_plugin_active($plugin_rel_path)) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $activate_res = activate_plugin($plugin_rel_path);
    if (is_wp_error($activate_res)) {
        echo "\033[1;31m[警告]\033[0m 插件激活返回: " . $activate_res->get_error_message() . " (尝试继续)\n";
    } else {
        echo "  \033[1;32m✓\033[0m 插件 wp-inquiry-chatbot 已激活！\n";
    }
} else {
    $current_ver = get_local_plugin_version($plugin_file);
    echo "  \033[1;32m✓\033[0m 插件已处于激活状态 (运行版本: v{$current_ver})。\n";
}
// 强制执行数据表结构同步
if (class_exists('WPI_DB')) {
    WPI_DB::create_tables();
    global $wpdb;
    $table = WPI_DB::get_table_name();
    $cnt = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "  \033[1;32m✓\033[0m 数据库表 {$table} 结构校验完成 (当前已有线索记录: {$cnt} 条)。\n";
} else {
    echo "\033[1;31m[错误]\033[0m 未检测到 WPI_DB 类，请确认插件文件完整性！\n";
    exit(1);
}

// -------------------------------------------------------------
// 4. 六大工业原型语料库定义 (含严密 @ 邮箱门禁)
// -------------------------------------------------------------
function get_archetype_questions($industry) {
    $base_whatsapp = [
        'id'          => 'whatsapp_contact',
        'question'    => 'Do you use WhatsApp or WeChat for quick engineering review and drawing confirmation?',
        'type'        => 'optional_text',
        'options'     => ['📲 Chat on WhatsApp', 'Prefer Email only ✉️'],
        'required'    => false,
        'placeholder' => 'Enter your WhatsApp/Phone or select above...',
    ];

    $base_volume = [
        'id'          => 'project_volume',
        'question'    => 'What is your estimated order quantity and project schedule?',
        'type'        => 'choice_or_text',
        'options'     => [
            'Prototype / 1-2 units sample test',
            'Trial batch (10 - 50 units)',
            'Roll-out / Mass production (> 100 units)',
            'Government / Enterprise Tender stage'
        ],
        'required'    => false,
        'placeholder' => 'e.g. 50 units needed in 2 months...',
    ];

    switch ($industry) {
        case 'kiosk':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'kiosk_mounting',
                    'question'    => 'Which kiosk mounting form factor fits your deployment location?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Floor-standing Totem (Free-standing)',
                        'Wall-mounted / Desktop Countertop',
                        'Open Frame / Flush Panel Mount',
                        'Outdoor Weatherproof (IP65/IP66)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select mounting style or describe your scenario...',
                ],
                [
                    'id'          => 'kiosk_peripherals',
                    'question'    => 'What peripheral cutouts or modular hardware devices do you need integrated?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'POS Bracket + 80mm Thermal Printer',
                        'QR / 2D Barcode Scanner + HD Camera',
                        'RFID / NFC / IC Card Dispenser',
                        'Touchscreen Display Only (No extra peripherals)'
                    ],
                    'required'    => false,
                    'placeholder' => 'List required cutouts/devices or select above...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'What screen size and orientation do you prefer?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '21.5" - 27" Vertical (Most Popular)',
                        '32" - 43" Large Interactive',
                        '10.1" - 15.6" Compact Countertop',
                        'Dual Screen (Touch + Ad Screen)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Preferred size or paste reference product link...',
                ],
                [
                    'id'          => 'host_specs',
                    'question'    => 'Which computer host or operating system do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Windows OS (Intel Core i3/i5/i7)',
                        'Android OS (Rockchip RK3568/RK3588)',
                        'Monitor Enclosure Only (Bring your own PC)',
                        'Standard Configuration (Recommend for me)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify CPU, RAM, SSD or OS preference...',
                ],
                $base_volume
            ];

        case 'touch_screen':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'mounting_type',
                    'question'    => 'What is your preferred structural installation and mounting method?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Open Frame (Embedded with side brackets)',
                        'Front IP65 Waterproof Flush Mount',
                        'Standard VESA 75/100mm Rear Mount',
                        'All-in-one Industrial Panel PC'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select structure or describe your cutout...',
                ],
                [
                    'id'          => 'touch_tech',
                    'question'    => 'Which touch technology and glass treatment is required?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'PCAP Capacitive (10-Point Multi-touch)',
                        'Resistive Touch (Glove & Stylus operation)',
                        'Optical Bonding + Anti-Glare / AG glass',
                        'Non-touch (Tempered protective glass only)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select touch method or specify glass thickness...',
                ],
                [
                    'id'          => 'brightness_env',
                    'question'    => 'What is the operating environment and screen brightness demand?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard Indoor (300 - 400 nits)',
                        'Semi-outdoor / Bright Ambient (500 - 800 nits)',
                        'Outdoor Sunlight Readable (1000 - 1500+ nits)',
                        'Wide Temperature (-20°C to +70°C)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify brightness in nits or ambient conditions...',
                ],
                [
                    'id'          => 'signal_interfaces',
                    'question'    => 'Which video signal and touch controller interfaces are needed?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'HDMI + VGA + USB Touch',
                        'HDMI + DisplayPort (DP) + DVI',
                        'RS232 Serial Touch + Audio I/O',
                        'DC 9V-36V Wide Voltage Input'
                    ],
                    'required'    => false,
                    'placeholder' => 'List required ports or signal inputs...',
                ],
                $base_volume
            ];

        case 'stretched_bar':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email to receive our Stretched Bar LCD catalog and cut-to-size quotation?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'bar_aspect_ratio',
                    'question'    => 'What is the intended installation space and bar LCD aspect ratio?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Retail Shelf-edge Bar (23.1", 28", 35" ultra-wide)',
                        'Public Transit / Subway / Bus Route Display',
                        'Ceiling-hanging Double-sided Stretched Screen',
                        'Custom Cut Stretched LCD Resizing'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify exact size, aspect ratio or shelf length...',
                ],
                [
                    'id'          => 'orientation_display',
                    'question'    => 'Will the screen be mounted in Landscape or Portrait orientation?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Horizontal Landscape (Shelf / Banner)',
                        'Vertical Portrait (Pillar / Header)',
                        'Wall Recessed Flush Installation',
                        'Double-sided Glass Mount'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select orientation or installation structure...',
                ],
                [
                    'id'          => 'cms_system',
                    'question'    => 'What content management and media player solution do you prefer?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Android Cloud CMS (Remote Wi-Fi/4G update)',
                        'Standalone USB Auto-loop Playback',
                        'Monitor Only (HDMI/DP input from external box)',
                        'Windows x86 Media Player'
                    ],
                    'required'    => false,
                    'placeholder' => 'Describe playback software or CMS platform...',
                ],
                [
                    'id'          => 'power_brightness',
                    'question'    => 'What power supply and screen brightness do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard Commercial (300-500 nits, AC 110-240V)',
                        'High Brightness Window Facing (700-1000 nits)',
                        'Vehicle DC 12V / 24V Wide Voltage',
                        'Support PoE (Power over Ethernet)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify nits, voltage or operating hours (24/7)...',
                ],
                $base_volume
            ];

        case 'lighting':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email to send the IES photometric files and project wholesale quotation?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'fixture_type',
                    'question'    => 'What type of commercial or architectural lighting fixture is required?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Recessed Aluminum Linear Lighting',
                        'Industrial High Bay / Tri-proof Light',
                        'Outdoor In-ground / Facade Architectural Wall Washer',
                        'Magnetic Track Light / Commercial Spotlight'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify luminaire type or paste product link...',
                ],
                [
                    'id'          => 'optical_specs',
                    'question'    => 'What are your color temperature (CCT) and optical requirements?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Warm White 3000K / Natural 4000K (CRI Ra>90)',
                        'Cool White 5000K - 6500K High Lumen',
                        'Tunable White (CCT 2700K - 6500K)',
                        'Narrow Beam (15°/24°) / Asymmetric Wall Wash'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify CCT, CRI, Lumen output or beam angle...',
                ],
                [
                    'id'          => 'dimming_control',
                    'question'    => 'What dimming protocol or smart control system is specified?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'DALI-2 / DALI Type 8 (DT8)',
                        '0-10V / 1-10V Analog Dimming',
                        'DMX512 Architectural Controller',
                        'Non-dimmable Constant Current'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify control protocol (e.g. DALI, Zigbee, 0-10V)...',
                ],
                [
                    'id'          => 'certification_driver',
                    'question'    => 'What driver brand and project certifications are required?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Mean Well / Tridonic / OSRAM Driver',
                        'CE, RoHS & CB Certified (European standard)',
                        'UL / cUL / DLC Listed (North American market)',
                        'IP65 / IP67 / IP68 High Protection Grade'
                    ],
                    'required'    => false,
                    'placeholder' => 'List required driver brand or compliance standards...',
                ],
                $base_volume
            ];

        case 'wear_parts':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email to receive our metallurgy material certificate and factory wholesale price list?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'machine_brand',
                    'question'    => 'Which excavator or machinery brand and series do you need parts for?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Caterpillar (CAT series)',
                        'Komatsu (PC series)',
                        'Hitachi / Volvo / Hyundai / Doosan',
                        'Custom Fabrication / OEM from Drawing'
                    ],
                    'required'    => false,
                    'placeholder' => 'Enter machine model (e.g. CAT 320, PC200)...',
                ],
                [
                    'id'          => 'part_category',
                    'question'    => 'What wear parts or ground engaging tools (GET) do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Bucket Teeth, Adapters & Pins (J-Series, etc.)',
                        'Cutting Edges, End Bits & Grader Blades',
                        'Track Rollers, Idlers & Undercarriage Parts',
                        'Hydraulic Breaker Chisels & Wear Plates'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify part numbers (e.g. 1U3352 / J350)...',
                ],
                [
                    'id'          => 'working_conditions',
                    'question'    => 'What is the working condition and target metallurgy specification?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Heavy Rock Quarry & Mining (High Impact)',
                        'General Earthmoving & Construction',
                        'High Abrasion Sand & Gravel',
                        'Forged Alloy Steel (HRC 48-52)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Describe ground condition or hardness requirement...',
                ],
                [
                    'id'          => 'packaging_shipping',
                    'question'    => 'What is your preferred packaging and shipment delivery mode?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'FOB China Port (Standard Fumigated Wooden Box)',
                        'CIF / CFR to Destination Sea Port',
                        'DDP Door-to-Door (Customs cleared)',
                        'Air Express for Urgent Prototyping'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify destination port or shipping terms...',
                ],
                $base_volume
            ];

        case 'battery':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email to send our UN38.3 test report and factory direct price list?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'battery_capacity',
                    'question'    => 'What system voltage and energy storage capacity do you need?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '12.8V / 25.6V Deep Cycle (40Ah - 230Ah for RV / Marine / Camping)',
                        '48V / 51.2V 100Ah (5.12 kWh Standard Residential ESS)',
                        '48V / 51.2V 200Ah / 280Ah (10 - 15 kWh Large Capacity Tower)',
                        '3.2V 100Ah - 280Ah Prismatic LiFePO4 Cells (DIY Assembly)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify kWh capacity, voltage or peak power...',
                ],
                [
                    'id'          => 'structure_type',
                    'question'    => 'What structural form factor suits your installation site?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Portable Outdoor Battery Box (Camping / Mobile Power / RV)',
                        'Wall-mounted Residential Powerwall (Indoor Home ESS)',
                        'Standard 19-inch Server Rack Mount / Stackable Tower',
                        'Golf Cart / EV / Marine Waterproof Metal Enclosure'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select structure or describe installation space...',
                ],
                [
                    'id'          => 'inverter_protocol',
                    'question'    => 'Which inverter brand or BMS communication protocol is required?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Deye / Growatt / Victron / GoodWe / Solis (CAN / RS485)',
                        'Smart Bluetooth BMS with Mobile App Monitoring',
                        'Standalone DC Deep Cycle (No inverter protocol required)',
                        'Need Complete Package (Matching Hybrid Inverter Included)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Enter inverter brand and model...',
                ],
                [
                    'id'          => 'cell_cert',
                    'question'    => 'What cell grade and international certifications are needed?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Grade-A EVE / CATL / REPT LiFePO4 (6000+ Deep Cycles)',
                        'CE, UN38.3, MSDS Certified (Standard Sea/Air Shipping)',
                        'UL1973, UL9540A & IEC62619 Tested for US / EU Grid',
                        'Yinlong LTO Cylindrical Cells (-40°C Extreme Temperature)'
                    ],
                    'required'    => false,
                    'placeholder' => 'List required certificates or warranty terms...',
                ],
                $base_volume
            ];
        case 'interactive_table':
        case 'touch_table':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com).',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'table_structure',
                    'question'    => 'Which table base structure or form factor best suits your venue?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'U-shaped base (Modern lounge / activity table)',
                        'T-shaped base (Conference / office collaboration)',
                        'Motorized Lifting table (Adjustable height / tilt)',
                        'Coffee table with drawers / X-shaped base'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select table style or describe custom structure...',
                ],
                [
                    'id'          => 'interactive_features',
                    'question'    => 'What interactive functions and surface protection do you need?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'IP65 Waterproof / Spill-proof tempered glass',
                        'Object Recognition (Tangible interactive tokens / pucks)',
                        'Multi-user PCAP touch (10 / 20 / 40 touch points)',
                        'Integrated wireless phone chargers / power sockets'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select key features or describe custom requirements...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'What screen size and resolution do you prefer for your table?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '43-inch 4K UHD (Most popular commercial size)',
                        '55-inch 4K UHD (Spacious group collaboration)',
                        '21.5" - 32" Full HD (Compact tea / lounge table)',
                        '65" - 86" Large format (Exhibition / command center)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select screen size or specify dimensions...',
                ],
                [
                    'id'          => 'host_specs',
                    'question'    => 'Which operating system and hardware configuration do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Android OS (Plug & Play, WiFi, App support)',
                        'Windows 10/11 Pro (Core i5/i7 for 3D/Unity/Software)',
                        'Monitor only (HDMI + USB touch for external PC)',
                        'Dual OS (Windows + Android switchable)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify OS, CPU/RAM, or external PC connection...',
                ],
                $base_volume
            ];
        case 'solar_charging':
        case 'solar_station':
        case 'solar_bench':
        case 'charging_station':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com).',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'station_type',
                    'question'    => 'Which type of solar charging station or structure fits your site?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Smart Solar Bench (Park / Campus / Walkway)',
                        'Outdoor Solar Kiosk with LCD Advertising Display',
                        'Freestanding Solar Charging Station / Pole',
                        'Portable Fold Solar Panel + Power Station'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select station type or describe your installation venue...',
                ],
                [
                    'id'          => 'solar_battery',
                    'question'    => 'What solar panel wattage and battery backup capacity do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard Solar (100W-200W) + Built-in LiFePO4 Battery',
                        'High Power (300W-400W+) + 3-5 Days Rainy Day Autonomy',
                        'Hybrid Solar + AC Grid Backup Power (Auto-switch)',
                        'Custom Off-Grid Power System (Need Engineering Advice)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select power capacity or specify your autonomy days...',
                ],
                [
                    'id'          => 'charging_ports',
                    'question'    => 'What charging methods and port configurations do you prefer?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Qi Fast Wireless Pads (15W) + USB-A & Type-C PD Ports',
                        'Multi-Port Fast Charging Hub (6-12 High-Speed USB Ports)',
                        'AC Sockets (110V/220V for Laptops) + Wireless Charging',
                        'Secure Phone Charging Lockers / Storage Compartments'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select port options or specify connector types needed...',
                ],
                [
                    'id'          => 'smart_iot',
                    'question'    => 'Do you need smart IoT or value-added auxiliary features?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Outdoor High-Brightness LCD Display (CMS Cloud Signage)',
                        'Public 4G / Wi-Fi Hotspot Router & Antenna',
                        'LED Night Ambience & Sensor Courtesy Lighting',
                        'Standard Solar Station (Charging Only, No Extras)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select auxiliary features or enter custom requirements...',
                ],
                $base_volume
            ];
        case 'video_wall':
        case 'lcd_video_wall':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the video wall proposal & quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'bezel_size',
                    'question'    => 'What screen unit size and physical bezel seam width do you prefer?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '55" 0.88mm Ultra-Narrow Bezel (Seamless Command Center)',
                        '55" 1.74mm / 1.8mm Razor Narrow Bezel (Popular for Control Rooms)',
                        '55" / 49" / 46" 3.5mm Commercial Bezel (Cost-effective CCTV)',
                        '65" Large Format Splicing Unit (0.88mm / 3.5mm)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select unit size & bezel or specify required dimensions...',
                ],
                [
                    'id'          => 'matrix_layout',
                    'question'    => 'What matrix layout or video wall array do you plan to deploy?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '2x2 (4 screens) / 3x2 (6 screens) Compact Wall',
                        '3x3 (9 screens) Standard Command Center',
                        '4x4 (16 screens) / 4x6 / 5x5 Large Scale Wall',
                        'Custom Rows × Columns (Paste wall dimensions W×H)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select matrix layout or enter your room width & height...',
                ],
                [
                    'id'          => 'bracket_structure',
                    'question'    => 'Which mounting bracket and maintenance structure fits your site installation?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Front Hydraulic Push-out Rack (Easy pop-out maintenance)',
                        'Floor-standing Aluminum Profile Bracket (Heavy-duty)',
                        'Wall-mounted Fixed Bracket (Slim flat mount)',
                        'Floor Cabinet Base (Integrated equipment enclosure)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select mounting solution or describe installation wall/space...',
                ],
                [
                    'id'          => 'splicing_processor',
                    'question'    => 'What video wall controller or signal processing solution do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard Multi-Screen Loop-through (HDMI/DP Daisy Chain)',
                        '4K Video Wall Matrix Processor (Multi-window Roaming/PIP)',
                        'IP Network Distributed Matrix (AV over IP for CCTV / NOC)',
                        'Display Panels & Brackets Only (We have our own controller)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify signal inputs (HDMI/DVI/SDI), windows, or controller...',
                ],
                $base_volume
            ];
        case 'outdoor_lcd':
        case 'outdoor_display':
        case 'outdoor_signage':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com).',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'installation_form',
                    'question'    => 'What is your project application and enclosure installation style?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Smart Bus Shelter / Transit Info Totem',
                        'Floor-Standing Totem (Single-Sided)',
                        'Dual-Sided Outdoor Totem (Double-Screen)',
                        'Wall-Mounted IP65 Outdoor Display'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select form factor or describe your installation environment...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'What screen size do you prefer for this outdoor display?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '55 Inch (Most Popular Standard)',
                        '65 Inch (High-Traffic Transit)',
                        '75 / 86 Inch (Large-Format Totem)',
                        '32 / 43 Inch (Compact Wall-Mount)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select size or specify custom dimensions...',
                ],
                [
                    'id'          => 'brightness_cooling',
                    'question'    => 'What are your requirements for brightness and thermal cooling?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '2500 nits + Smart Fan Cooling (-20°C ~ +50°C)',
                        '3000~4000 nits + Industrial Air Conditioner (Extreme Heat)',
                        '2000 nits Semi-Outdoor / Covered Shelter',
                        'Include Ambient Light Sensor (Auto Dimming)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify brightness or target ambient temperature range...',
                ],
                [
                    'id'          => 'system_player',
                    'question'    => 'Which operating system and media playback solution is required?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Android CMS Cloud Player (WiFi / 4G Remote Scheduling)',
                        'Windows Industrial PC (Intel Core i5/i7)',
                        'Monitor Only (HDMI / DP input for external player)',
                        'Include PCAP 10-Point Touch Screen'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify OS, touch requirement, or software platform...',
                ],
                $base_volume
            ];
        case 'holographic':
        case '3d_hologram':
        case 'hologram':
        case '3d_display':
        case 'showcase':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the 3D demo videos & factory quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'display_format',
                    'question'    => 'Which 3D holographic display format or showcase fits your application?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '3D Holographic Showcase (180° / 270° / 360° Pyramid Cabinet)',
                        'Transparent OLED / LCD Showcase (For Luxury Retail, Jewelry & Museum)',
                        'Glasses-Free 3D Commercial Display (Single Screen 32" - 110" 4K/8K)',
                        '3D Holographic Splicing Video Wall (Multi-Screen Light Field Array)',
                        '3D Holographic LED Fan (Single Unit or Multi-Fan Matrix Wall)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select showcase type or describe your exhibition requirement...',
                ],
                [
                    'id'          => 'optical_features',
                    'question'    => 'What optical viewing technology and interactive features do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Multi-View Light Field (Multi-person wide viewing without 3D glasses)',
                        'Real-Time Eye-Tracking 3D (Deep spatial depth with camera tracking)',
                        'PCAP Multi-Touch Interactive (Touch to rotate/zoom 3D product models)',
                        'Ultra High Brightness (700 - 2500 nits for retail window & commercial mall)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select optical feature or specify viewing angle requirements...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'What screen size or showcase cabinet dimension do you prefer?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Compact Desktop / Countertop (13.3", 21.5", 27", 32")',
                        'Standard Commercial / Totem (43", 50", 55", 65")',
                        'Large-Format Landmark (85", 86" Portrait 8K, 98", 110")',
                        'Custom Built Showcase Cabinet (Tailored dimensions for exhibits)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select size or specify custom cabinet L*W*H dimensions...',
                ],
                [
                    'id'          => 'host_specs',
                    'question'    => 'Which media controller and content playback system do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Android Media Player (Plug & Play, WiFi/LAN, Remote CMS Loop)',
                        'Windows 10/11 Pro (Core i5/i7 + Dedicated GPU for Unity/Unreal 3D)',
                        'Pure Monitor Mode (HDMI 2.0/2.1 + USB for External PC Server)',
                        'Need Custom 3D Holographic Animation & Modeling Support'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify OS, GPU/CPU, CMS, or 3D content creation needs...',
                ],
                $base_volume
            ];
        case 'smart_board':
        case 'smartboard':
        case 'ifpd':
        case 'interactive_flat_panel':
        case 'whiteboard':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email to send the complete LCD Smart Board spec sheet, CAD drawing and wholesale quotation?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'application_scenario',
                    'question'    => 'What is your primary application scenario and room environment?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Smart Classroom / K-12 & Higher Education (教学交互)',
                        'Corporate Meeting Room / Hybrid Video Conference (企业会议)',
                        'Training Center & Presentation Auditorium (多功能培训厅)',
                        'Government & Exhibition Hall / Command Center (政企展厅)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select scenario or describe your venue dimensions...',
                ],
                [
                    'id'          => 'touch_technology',
                    'question'    => 'Which touch technology and screen bonding process do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'High-precision IR 20/40-Point Touch (Cost-Effective & Durable)',
                        'PCAP Capacitive Multi-touch (Ultra-smooth Flush Glass)',
                        'Zero-gap / Optical Bonding + Anti-Glare (AG) 4mm Tempered Glass',
                        'Standard Infrared Touch with Anti-Glare Treatment'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select touch technology or specify glass requirements...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'What screen size fits your classroom or conference space?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '65" 4K UHD (Most popular for 10-20 people / standard classroom)',
                        '75" 4K UHD (Best for 20-30 people conference room)',
                        '86" 4K UHD (Recommended for 30-50 people large boardroom)',
                        '55" (Small Huddle Room) / 98"-110" (Grand Auditorium)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Preferred size (55", 65", 75", 85", 86", 98", 100", 110")...',
                ],
                [
                    'id'          => 'system_accessories',
                    'question'    => 'Which operating system (OS) and collaboration accessories are needed?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Dual OS: Android 13/14 + Windows OPS i5/i7 (One-click switch)',
                        'Android OS Only (4G+32G / 8G+128G with Whiteboard software)',
                        'With 4K Auto-Tracking Camera + 8-Array Mic + Rolling Stand Cart',
                        'Monitor Mode (HDMI In + Touch USB) + Heavy-Duty Wall Mount'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify OS, OPS PC specs (i5/i7/RAM/SSD) or accessories...',
                ],
                $base_volume
            ];
        case 'retail_signage':
        case 'commercial_signage':
        case 'ad_display':
        case 'digital_signage':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the wholesale catalog.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'mounting_form',
                    'question'    => 'Which digital signage mounting form factor best fits your retail store or venue layout?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Wall-Mounted Android Advertising Display (19" - 55" Slim Bezel)',
                        'Floor-Standing Digital Poster Totem (50" / 55" Tempered Glass)',
                        'Hanging Double-Sided Window Display (High Brightness)',
                        'Portable Battery Powered A-Board (43" / 49" Folding Sidewalk)',
                        'Countertop / Shelf-Edge POS Display (10.1" / 15.6")'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select mounting style or describe your store layout...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'What screen size and resolution do you prefer for your stores or deployment sites?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '43" / 50" / 55" Commercial IPS (Most Popular for Retail Promotion)',
                        '32" Digital Menu Board / Wall Kiosk',
                        '10.1" / 15.6" / 21.5" Countertop & POS Screen',
                        '65" / 75" / 86" 4K Large Format Display / Video Wall'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select screen size or paste reference product model...',
                ],
                [
                    'id'          => 'brightness_features',
                    'question'    => 'What brightness level and interactive features do your store environments require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard Indoor 450 cd/m² (Retail Shop, Showroom, 24/7 IPS Panel)',
                        'Window-Facing High Brightness 700 - 1500 cd/m² (Sunlight Visible)',
                        'Outdoor Weatherproof IP65 2500 - 3500 cd/m²',
                        'Multi-Touch Screen (PCAP 10-Point Touch for Interactive Navigation)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select brightness / touch or specify ambient lighting conditions...',
                ],
                [
                    'id'          => 'system_cms',
                    'question'    => 'Which operating system and content management solution do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Android OS + Cloud CMS (Remote Multi-Store Wi-Fi/4G/LAN Update)',
                        'Plug & Play Standalone (USB Auto-Loop Video / Images)',
                        'Windows 10/11 Pro (OPS Intel Core i5/i7 Industrial PC)',
                        'Monitor Only (HDMI / VGA Video Input for External Player)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify OS version, CMS software, or RAM/Storage requirements...',
                ],
                $base_volume
            ];
        case 'interactive_projection':
        case 'ar_projection':
        case 'kids_playground':
        case 'interactive_playground':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the product catalog and factory quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                [
                    'id'          => 'whatsapp_contact',
                    'question'    => 'Do you use WhatsApp or WeChat for quick video demonstration and quotation?',
                    'type'        => 'optional_text',
                    'options'     => ['📲 Chat on WhatsApp (+8618098949445)', 'Prefer Email only ✉️'],
                    'required'    => false,
                    'placeholder' => 'Enter your WhatsApp/Phone or select above...',
                ],
                [
                    'id'          => 'interactive_game_type',
                    'question'    => 'Which interactive projection game or attraction type are you looking for?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Interactive Smash Ball Wall / Ball Pool (互动砸球/波波池投影墙)',
                        'AR Interactive Sand Table / Sand Beach (AR 互动沙桌/魔法沙滩)',
                        'Interactive Trampoline / Slide / Floor (互动蹦床/滑梯/地面互动)',
                        'Interactive Sports Simulators: Soccer / Tennis (足球/网球/攀岩体感模拟)',
                        'Draw Alive Magic Painting & Immersive Room (神笔画画/全息沉浸式教室)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select game type or describe your play attraction...',
                ],
                [
                    'id'          => 'hardware_package',
                    'question'    => 'What hardware setup and supply mode do you prefer?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Turnkey System: Projector + Host PC + Radar/Motion Sensor + Enclosure (整套全包交付)',
                        'Sensor + Game Software + Host PC only (已有投影机，仅需体感套件+软件主机)',
                        'Software License + 3D Games Package only (已有硬件，仅需正版互动软件与游戏库)',
                        'Whole Park Custom Design with Balls / Pool Enclosure (整馆一站式定制含软包外框)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select hardware package or specify your existing equipment...',
                ],
                [
                    'id'          => 'projection_dimension',
                    'question'    => 'What is your estimated projection area or wall/room dimension?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Single Wall / Ground: 3m - 5m width (单台投影标准画面)',
                        'Multi-Projector Splicing: 6m - 10m+ width (多机大画面融合拼接)',
                        'Interactive Sand Table / Slide: 1.5m - 2.5m (沙桌/滑梯小型专用)',
                        'Immersive 3-4 Sided 360° Room (全息沉浸式三折幕/四折幕空间)'
                    ],
                    'required'    => false,
                    'placeholder' => 'e.g. 4m wide x 2.5m high, or paste room CAD size...',
                ],
                [
                    'id'          => 'game_customization',
                    'question'    => 'Do you have specific game content or brand customization requirements?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard 30-50+ HD Games Library (含出厂30-50款双语益智竞技游戏库)',
                        'Custom Brand Logo / Holiday Theme Integration (植入品牌Logo/节日定制)',
                        'New Customized Game Development (全新专属玩法定制开发)',
                        'Regular Remote Game Update Service (支持后续远程OTA游戏升级)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select option or describe your custom theme...',
                ],
                [
                    'id'          => 'project_volume',
                    'question'    => 'What is your estimated order quantity and project schedule?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '1-2 units (Sample test / Single attraction upgrade)',
                        '3-5 units (Complete playground / Multi-zone project)',
                        '10+ units (Distributor wholesale / Chain FEC rollout)',
                        'Commercial Tender / Planning stage'
                    ],
                    'required'    => false,
                    'placeholder' => 'e.g. 2 sets needed for opening next month...',
                ]
            ];
        case 'touch_signage':
        case 'thai_signage':
        case 'lansentouch':
        case 'thai_touch':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'สวัสดีครับ! ขอทราบชื่อและอีเมลสำหรับติดต่อธุรกิจ (Business Email) ของท่านได้ไหมครับ?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'กรุณากรอกอีเมลสำหรับธุรกิจที่ถูกต้องและมีเครื่องหมาย "@" (เช่น name@company.com) เพื่อให้เราจัดส่งข้อมูลและใบเสนอราคาครับ',
                    'placeholder'      => 'ชื่อและอีเมลธุรกิจของคุณ (มี @)...',
                ],
                [
                    'id'          => 'whatsapp_contact',
                    'question'    => 'ท่านสะดวกติดต่อผ่าน WhatsApp หรือ WeChat เพื่อรับข้อมูลและใบเสนอราคาอย่างรวดเร็วไหมครับ?',
                    'type'        => 'optional_text',
                    'options'     => [
                        '📲 ติดต่อทาง WhatsApp (+8618098949445)',
                        '💬 ติดต่อทาง WeChat (ZXT18098949445)',
                        '✉️ สะดวกติดต่อทางอีเมลเท่านั้น (Email Only)'
                    ],
                    'required'    => false,
                    'placeholder' => 'กรอกเบอร์โทร / WhatsApp / LINE หรือเลือกตัวเลือกด้านบน...',
                ],
                [
                    'id'          => 'installation_structure',
                    'question'    => 'ท่านต้องการจอโฆษณาหรือตู้คีออสในรูปแบบโครงสร้างและการติดตั้งใด?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'ตู้คีออสตั้งพื้น ทรงมาตรฐาน (Floor-standing Totem สำหรับห้างสรรพสินค้า/โรงภาพยนตร์)',
                        'ป้ายโฆษณาดิจิทัลแบบแขวน/ติดผนัง (Wall-mounted Commercial Display)',
                        'ตู้สั่งอาหาร/ชำระเงินบริการตนเอง (Self-Service Ordering / Payment Kiosk)',
                        'จอสัมผัสแบบฝังเคาน์เตอร์ / Open Frame (Flush Mount / Control Cabinet)'
                    ],
                    'required'    => false,
                    'placeholder' => 'เลือกรูปแบบด้านบน หรือระบุลักษณะพื้นที่ติดตั้งที่ต้องการ...',
                ],
                [
                    'id'          => 'touch_tech',
                    'question'    => 'ท่านต้องการระบบสัมผัส (Touch Screen) และคุณสมบัติหน้าจอแบบใด?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'สัมผัส Capacitive PCAP 10-Point (ตอบสนองไว ผิวเรียบเสมอกระจก)',
                        'สัมผัสอินฟราเรด IR Multi-Touch (ทนทาน เหมาะสำหรับพื้นที่สาธารณะ)',
                        'กระจกนิรภัยป้องกันแสงสะท้อน AG Glass (Anti-Glare คมชัดสูง)',
                        'จอแสดงผลโฆษณาอย่างเดียว ไม่ต้องสัมผัส (Non-touch Display Only)'
                    ],
                    'required'    => false,
                    'placeholder' => 'เลือกระบบสัมผัส หรือระบุความต้องการเพิ่มเติม...',
                ],
                [
                    'id'          => 'screen_size',
                    'question'    => 'ท่านสนใจหน้าจอขนาดกี่นิ้ว สำหรับโครงการของท่าน?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        '43 นิ้ว / 49 นิ้ว (ขนาดยอดนิยมสำหรับจุดบริการข้อมูลและโฆษณาในห้าง)',
                        '55 นิ้ว / 65 นิ้ว (ขนาดใหญ่สำหรับโรงภาพยนตร์ ห้างสรรพสินค้า และโชว์รูม)',
                        '21.5 นิ้ว / 27 นิ้ว / 32 นิ้ว (ขนาดกะทัดรัด สำหรับตู้สั่งอาหารและชำระเงิน)',
                        '75 นิ้ว - 86 นิ้วขึ้นไป (จอยักษ์ระดับพรีเมียม / Interactive Presentation)'
                    ],
                    'required'    => false,
                    'placeholder' => 'เลือกขนาดหน้าจอ หรือวางลิงก์สินค้าที่สนใจ...',
                ],
                [
                    'id'          => 'system_player',
                    'question'    => 'ท่านต้องการระบบปฏิบัติการและสเปกเครื่องควบคุม (Player OS) แบบใด?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'ระบบ Android (พร้อม WiFi/LAN สำหรับระบบ CMS จัดการโฆษณาออนไลน์)',
                        'ระบบ Windows 10/11 Pro (Intel Core i3/i5/i7 สำหรับโปรแกรมซอฟต์แวร์เฉพาะ)',
                        'Dual OS (รองรับทั้ง Android และ Windows สลับใช้งานได้)',
                        'เฉพาะจอ Monitor (มีพอร์ต HDMI + USB Touch สำหรับต่อคอมพิวเตอร์ภายนอก)'
                    ],
                    'required'    => false,
                    'placeholder' => 'ระบุระบบปฏิบัติการ, CPU, RAM หรือโปรแกรมที่ต้องการใช้งาน...',
                ],
                [
                    'id'          => 'project_volume',
                    'question'    => 'จำนวนที่ท่านคาดว่าจะสั่งซื้อและระยะเวลาของโครงการเป็นอย่างไรครับ?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'สั่งซื้อเครื่องตัวอย่างทดสอบ (Prototype 1 - 2 เครื่อง)',
                        'สั่งซื้อล็อตทดลอง (Trial Batch 5 - 20 เครื่อง)',
                        'สั่งซื้อจำนวนมาก / โครงการใหญ่ (Mass Production > 50 เครื่อง)',
                        'อยู่ในขั้นตอนประมูลโครงการ / ร่าง TOR (ต้องการสเปกและราคาประเมิน)'
                    ],
                    'required'    => false,
                    'placeholder' => 'เช่น ต้องการ 10 เครื่องภายใน 1-2 เดือน...',
                ],
            ];
        case 'medical_aesthetic':
        case 'aesthetic_device':
        case 'aesthetic':
        case 'regenerative_aesthetic':
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello, may I have your Name and Business Email?',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. dr.name@clinic.com or name@company.com).',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                [
                    'id'          => 'whatsapp_contact',
                    'question'    => 'Do you use WhatsApp or WeChat for instant clinical specifications and wholesale quotation?',
                    'type'        => 'optional_text',
                    'options'     => [
                        '📲 Chat on WhatsApp (+8618098949445)',
                        'Prefer Email only ✉️'
                    ],
                    'required'    => false,
                    'placeholder' => 'Enter your WhatsApp/Phone or select above...',
                ],
                [
                    'id'          => 'device_type',
                    'question'    => 'Which aesthetic device modality or regenerative system are you looking for?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'RF Microneedling (Dermal penetration for PRP/PRF/Exosome)',
                        'Fractional Anti-Aging & Skin Tightening Device',
                        'Medical Spa Multi-Function Platform / Trolley Unit',
                        'Custom Aesthetic Equipment OEM / ODM Manufacturing'
                    ],
                    'required'    => false,
                    'placeholder' => 'Select modality or describe desired treatment system...',
                ],
                [
                    'id'          => 'dermal_technology',
                    'question'    => 'What dermal penetration technology and energy specifications do you require?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'RF Microneedling + PRP / PRF / Exosome Delivery',
                        'Adjustable Depth RF (0.5mm - 4.0mm targeted penetration)',
                        'Insulated & Non-Insulated Needle Cartridges (25/49/64 pins)',
                        'Cryo Cold Soothing Hammer + Negative Pressure Vacuum'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify penetration depth, RF frequency, or cartridge requirements...',
                ],
                [
                    'id'          => 'customization_oem',
                    'question'    => 'What level of OEM/ODM customization or branding do you need for your clinics?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Private Label (Custom chassis color, silk-screen logo & casing)',
                        'Custom Software UI (Logo on startup screen, localized languages)',
                        'Complete Machine + Clinical Trolley Stand Turnkey Solution',
                        'Standard Factory Model (Fast delivery / ready to test)'
                    ],
                    'required'    => false,
                    'placeholder' => 'Describe OEM branding, software customization, or exterior design...',
                ],
                [
                    'id'          => 'regulatory_market',
                    'question'    => 'Which clinical certifications and target market standards are required?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'FDA Cleared / 510(k) Ready Standard',
                        'CE Medical / ISO 13485 Certified Facility',
                        'Standard CE & RoHS for Commercial Aesthetic Clinics',
                        'OEM Technical File & Safety Compliance Support'
                    ],
                    'required'    => false,
                    'placeholder' => 'Specify regulatory certifications or voltage (110V/220V)...',
                ],
                [
                    'id'          => 'project_volume',
                    'question'    => 'What is your estimated order quantity and procurement timeline?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Evaluation / 1-2 units clinic sample test',
                        'Clinic & MedSpa batch (5 - 20 units)',
                        'Distributor OEM batch (> 50 - 100 units)',
                        'Custom Clinic Chain / Hospital Tender stage'
                    ],
                    'required'    => false,
                    'placeholder' => 'e.g. 5 units needed for new clinics in 2 months...',
                ]
            ];

        default: // generic
            return [
                [
                    'id'               => 'name_email',
                    'question'         => 'Hello! Please enter your Name and Business Email to receive our wholesale quotation and engineering specifications:',
                    'type'             => 'name_email',
                    'required'         => true,
                    'require_at_email' => true,
                    'prompt_error'     => 'Please provide a valid business email containing "@" (e.g. name@company.com) so we can send the quotation.',
                    'placeholder'      => 'Your Name & Business Email with @...',
                ],
                $base_whatsapp,
                [
                    'id'          => 'custom_spec',
                    'question'    => 'What are your core specification requirements or preferred model?',
                    'type'        => 'choice_or_text',
                    'options'     => [
                        'Standard Catalog Model (Send datasheet)',
                        'Custom OEM Dimensions & Branding',
                        'High-Performance Industrial Grade',
                        'Recommend best-selling model for me'
                    ],
                    'required'    => false,
                    'placeholder' => 'Describe specs, model number or paste link...',
                ],
                $base_volume
            ];
    }
}

// -------------------------------------------------------------
// 5. 注入表单语料与配置
// -------------------------------------------------------------
echo "\033[1;34m[3/6]\033[0m 正在配置追问表单流程与默认预设 ...\n";

$industry = strtolower(trim($options['industry'] ?? 'generic'));
$questions = null;

if (!empty($options['corpus'])) {
    $corpus_file = $options['corpus'];
    if (!file_exists($corpus_file)) {
        echo "\033[1;31m[错误]\033[0m 指定的语料文件 {$corpus_file} 不存在！\n";
        exit(1);
    }
    $raw_json = file_get_contents($corpus_file);
    $questions = json_decode($raw_json, true);
    if (!is_array($questions)) {
        echo "\033[1;31m[错误]\033[0m 语料文件 {$corpus_file} 不是合法的 JSON！\n";
        exit(1);
    }
    echo "  \033[1;32m✓\033[0m 已成功从文件加载自定义表单语料: {$corpus_file}\n";
} else {
    $questions = get_archetype_questions($industry);
    echo "  \033[1;32m✓\033[0m 已选定工业原型: \033[1;33m{$industry}\033[0m (含 7 步工程核算流与绝对商业邮箱门禁)。\n";
}

// 写入在线表单流程
update_option('wpi_form_questions', $questions);
// 启用机器人
update_option('wpi_enabled', '1');

// 固化为站点专属永久默认预设 (防手误误重置)
update_option('wpi_custom_default_questions', $questions);
update_option('wpi_custom_default_saved_at', current_time('mysql'));
echo "  \033[1;32m✓\033[0m 已自动固化为「站点专属默认预设」 (wpi_custom_default_questions)，后续防误触丢失。\n";

// 欢迎语配置
if (!empty($options['welcome'])) {
    update_option('wpi_welcome_msg', trim($options['welcome']));
    echo "  \033[1;32m✓\033[0m 欢迎语设置为: " . esc_html($options['welcome']) . "\n";
} else {
    if (in_array($industry, ['touch_signage', 'thai_signage', 'lansentouch', 'thai_touch'])) {
        update_option('wpi_welcome_msg', 'สวัสดีครับ! สนใจใบเสนอราคาส่งตรงจากโรงงานหรือสเปกของ "{product_name}" ใช่ไหมครับ?');
        update_option('wpi_default_welcome_msg', 'สวัสดีครับ! ยินดีต้อนรับสู่ LansenTouch ผู้ผลิตจอสัมผัสและป้ายโฆษณาดิจิทัล มีอะไรให้เราช่วยดูแลโครงการของคุณวันนี้ครับ?');
    } else {
        // 自动清除欢迎语中的 CAD 痕迹
        $curr_welcome = get_option('wpi_welcome_msg', '');
        if (empty($curr_welcome) || stripos($curr_welcome, 'CAD') !== false) {
            update_option('wpi_welcome_msg', 'Hi! Looking for factory-direct wholesale prices or specifications for "{product_name}"?');
        }
    }
}

// 客服称呼与 WhatsApp
if (!empty($options['bot-name'])) {
    update_option('wpi_bot_name', sanitize_text_field($options['bot-name']));
    echo "  \033[1;32m✓\033[0m 客服称呼设置为: " . esc_html($options['bot-name']) . "\n";
}
if (!empty($options['whatsapp'])) {
    update_option('wpi_whatsapp_number', sanitize_text_field($options['whatsapp']));
    echo "  \033[1;32m✓\033[0m WhatsApp 设置为: " . esc_html($options['whatsapp']) . "\n";
}

// -------------------------------------------------------------
// 6. 飞书 Webhook 与通知挂载
// -------------------------------------------------------------
echo "\033[1;34m[4/6]\033[0m 检查飞书推送配置 ...\n";
$feishu_url = !empty($options['feishu']) ? esc_url_raw($options['feishu']) : get_option('wpi_webhook_url', '');
$feishu_secret = !empty($options['secret']) ? trim($options['secret']) : get_option('wpi_webhook_secret', '');

if (!empty($options['feishu'])) {
    update_option('wpi_webhook_url', $feishu_url);
    if (!empty($options['secret'])) {
        update_option('wpi_webhook_secret', $feishu_secret);
    }
    echo "  \033[1;32m✓\033[0m 飞书 Webhook 已更新绑定: " . substr($feishu_url, 0, 45) . "...\n";
} else {
    if (!empty($feishu_url)) {
        echo "  \033[1;32m✓\033[0m 沿用站点既有飞书 Webhook: " . substr($feishu_url, 0, 45) . "...\n";
    } else {
        echo "  \033[1;33m-\033[0m 暂未配置飞书 Webhook (后续可随时在 WP 后台填入或重新运行 CLI 配置)。\n";
    }
}

// 连通性测试
if (isset($options['test-feishu']) && !empty($feishu_url)) {
    echo "  \033[1;36m[测试]\033[0m 正在向飞书群发送交互测试卡片 ...\n";
    $test_lead = [
        'id'            => 9999,
        'customer_name' => 'CLI Verification Bot',
        'email'         => 'engineer.test@factory-export.com',
        'phone'         => $options['whatsapp'] ?? '+8618098949445',
        'intent_grade'  => 'S',
        'source_url'    => home_url(),
        'payload_json'  => json_encode([
            'product_name' => 'CLI 一键部署连通性测试',
            'answers'      => [
                'archetype'   => $industry,
                'status'      => 'Server-side setup verified successfully',
                'executed_at' => current_time('mysql'),
            ],
            'visit'        => [
                'country'   => 'CLI Host',
                'dwell_sec' => 88
            ]
        ])
    ];

    $test_res = WPI_Notifier::dispatch_feishu_card($feishu_url, $feishu_secret, $test_lead);
    if (!empty($test_res['success'])) {
        echo "  \033[1;32m✓ [飞书验证成功]\033[0m 飞书交互卡片推送成功！请在飞书外贸群查收测试卡片。\n";
    } else {
        echo "  \033[1;31m✗ [飞书推送失败]\033[0m 错误详情: " . ($test_res['message'] ?? 'Unknown') . "\n";
    }
}

// -------------------------------------------------------------
// 7. 清理整站页面缓存与对象缓存
// -------------------------------------------------------------
echo "\033[1;34m[5/6]\033[0m 正在刷新整站缓存 ...\n";

// 1. WP 原生 Object Cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "  \033[1;32m✓\033[0m WP Object Cache 已清空\n";
}

// 2. WP Super Cache
if (function_exists('wp_cache_clean_cache')) {
    global $file_prefix;
    wp_cache_clean_cache($file_prefix, true);
    echo "  \033[1;32m✓\033[0m WP Super Cache 已彻底清理\n";
}

// 3. LiteSpeed Cache
if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
    LiteSpeed_Cache_API::purge_all();
    echo "  \033[1;32m✓\033[0m LiteSpeed Cache 已全量刷新\n";
}

// 4. Seraphinite Accelerator
if (class_exists('\Seraphinite\Accelerator\Api') && method_exists('\Seraphinite\Accelerator\Api', 'clearCache')) {
    \Seraphinite\Accelerator\Api::clearCache();
    echo "  \033[1;32m✓\033[0m Seraphinite Accelerator 已清空\n";
}

// 5. WP Rocket
if (function_exists('rocket_clean_domain')) {
    rocket_clean_domain();
    echo "  \033[1;32m✓\033[0m WP Rocket Cache 已彻底清理\n";
}

// 5. 递归清理 wp-content/cache 目录下的残留静态 HTML
$cache_dir = WP_CONTENT_DIR . '/cache';
if (is_dir($cache_dir)) {
    $cleared_files = 0;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isFile()) {
                @unlink($file->getRealPath());
                $cleared_files++;
            }
        }
    } catch (Exception $e) {
        // ignore iterator error
    }
    echo "  \033[1;32m✓\033[0m wp-content/cache/ 静态缓存文件清理完成 (清除了 {$cleared_files} 个文件)\n";
}

// -------------------------------------------------------------
// 8. 终端总结报告
// -------------------------------------------------------------
$home_url = home_url();
$leads_url = admin_url('admin.php?page=wpi-leads');

echo "\033[1;34m[6/6]\033[0m \033[1;32m=== 配置成功完成！ ===\033[0m\n";
echo <<<SUMMARY
\033[1;36m------------------------------------------------------------------------\033[0m
  \033[1;37m站点地址\033[0m     : {$home_url}
  \033[1;37m工业原型\033[0m     : \033[1;33m{$industry}\033[0m
  \033[1;37m邮箱门禁\033[0m     : \033[1;32m严格校验 @ 符号 (Upfront Email-First)\033[0m
  \033[1;37m预设快照\033[0m     : \033[1;32m已固化为默认快照 (防误重置)\033[0m
  \033[1;37m线索管理后台\033[0m : {$leads_url}
\033[1;36m------------------------------------------------------------------------\033[0m
\033[1;32m✓ 前端实机页面已即刻生效，可打开无痕窗口访问 {$home_url} 测试发散追问流！\033[0m

SUMMARY;
