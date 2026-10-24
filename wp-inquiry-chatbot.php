<?php
/**
 * Plugin Name: WP Inquiry Chatbot (ECV Style)
 * Plugin URI: https://github.com/GGdm01/wp-inquiry-chatbot
 * Description: Lightweight, deterministic, ECV-style B2B inquiry & lead generation chatbot for WordPress. Supports English/Chinese bilingual configuration, zero LLM cost, 100% conversion focused, context-aware product lead capture.
 * Version: 1.4.5
 * Author: GGdm01
 * Text Domain: wp-inquiry-chatbot
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

define('WPI_VERSION', '1.4.5');
define('WPI_PLUGIN_FILE', __FILE__);
define('WPI_PATH', plugin_dir_path(__FILE__));
define('WPI_URL', plugin_dir_url(__FILE__));

require_once WPI_PATH . 'includes/class-db.php';
require_once WPI_PATH . 'includes/class-notifier.php';
require_once WPI_PATH . 'includes/class-rest-api.php';
require_once WPI_PATH . 'includes/class-admin.php';
require_once WPI_PATH . 'includes/class-updater.php';

// Initialize auto-updater
WPI_Updater::init();
// Load plugin textdomain for WordPress i18n
add_action('plugins_loaded', function () {
    load_plugin_textdomain('wp-inquiry-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Activation & Deactivation hooks
register_activation_hook(__FILE__, ['WPI_DB', 'create_tables']);
register_activation_hook(__FILE__, ['WPI_Admin', 'set_default_options']);

// Enqueue front-end scripts and styles
add_action('wp_enqueue_scripts', function () {
    // Only load if enabled in settings
    if (!get_option('wpi_enabled', '1')) {
        return;
    }

    wp_enqueue_style('wpi-chatbot-style', WPI_URL . 'assets/css/chatbot.css', [], WPI_VERSION);
    wp_enqueue_script('wpi-chatbot-script', WPI_URL . 'assets/js/chatbot.js', [], WPI_VERSION, true);

    // Pass configuration to frontend (100% English corpus & dialogue defaults)
    wp_localize_script('wpi-chatbot-script', 'WPI_CONFIG', [
        'apiUrl'            => esc_url_raw(rest_url('wpi/v1/')),
        'nonce'             => wp_create_nonce('wp_rest'),
        'botName'           => get_option('wpi_bot_name', 'Sales Specialist'),
        'botAvatar'         => get_option('wpi_bot_avatar', ''),
        'welcomeMsg'        => get_option('wpi_welcome_msg', 'Hi! Looking for factory-direct wholesale prices or CAD specs for "{product_name}"?'),
        'defaultWelcomeMsg' => get_option('wpi_default_welcome_msg', 'Hi there! Welcome to our official site. How can we help your project today?'),
        'autoPopupSeconds'  => (int) get_option('wpi_auto_popup_seconds', 15),
        'exitIntent'        => (bool) get_option('wpi_exit_intent', 1),
        'scrollTrigger'     => (int) get_option('wpi_scroll_trigger', 50),
        'leadSuccessMsg'    => get_option('wpi_lead_success_msg', 'Thank you! Our engineering and sales team will contact you within 2 hours with the specs and quotation.'),
        'formQuestions'     => get_option('wpi_form_questions', WPI_Admin::get_default_form_questions()),
        'faqList'           => get_option('wpi_faq_list', WPI_Admin::get_default_faqs()),
        'whatsappNumber'    => get_option('wpi_whatsapp_number', ''),
        'language'          => get_option('wpi_language', 'en'),
    ]);
});

// Render chatbot DOM container in footer
add_action('wp_footer', function () {
    if (!get_option('wpi_enabled', '1')) {
        return;
    }
    $bot_name = get_option('wpi_bot_name', 'Sales Specialist');
    ?>
    <div id="wpi-widget" class="wpi-widget-container" aria-live="polite">
        <!-- Minimized Floating Button -->
        <button id="wpi-launcher" class="wpi-launcher-btn" aria-label="Open Inquiry Chat">
            <span class="wpi-badge-dot"></span>
            <svg class="wpi-icon-chat" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
            <svg class="wpi-icon-close" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Chat Modal Box -->
        <div id="wpi-chatbox" class="wpi-chatbox" style="display:none;">
            <!-- Header -->
            <div class="wpi-header">
                <div class="wpi-header-info">
                    <div class="wpi-avatar">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="white">
                            <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5zm0 12c-5.33 0-8 2.67-8 4v2h16v-2c0-1.33-2.67-4-8-4z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="wpi-title" id="wpi-bot-name"><?php echo esc_html($bot_name); ?></div>
                        <div class="wpi-status"><span class="wpi-status-dot"></span> <span id="wpi-status-text">Online | Direct Factory Reply</span></div>
                    </div>
                </div>
                <button id="wpi-close-btn" class="wpi-header-btn" title="Minimize">&times;</button>
            </div>

            <!-- Context Banner (ECV Product Preview) -->
            <div id="wpi-context-banner" class="wpi-context-banner" style="display:none;">
                <div class="wpi-context-tag" id="wpi-context-tag">Currently Viewing:</div>
                <div class="wpi-context-title" id="wpi-context-title"></div>
            </div>

            <!-- Messages Stream -->
            <div id="wpi-messages" class="wpi-messages"></div>

            <!-- Quick Submit Bar (Appears once contact is captured) -->
            <div id="wpi-quick-submit-bar" class="wpi-quick-submit-bar" style="display:none;">
                <button type="button" id="wpi-quick-submit-btn" class="wpi-quick-submit-btn">
                    <span class="wpi-quick-submit-icon">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </span>
                    <span class="wpi-quick-submit-text" id="wpi-quick-submit-text">Submit Inquiry Directly</span>
                    <span class="wpi-quick-submit-tag" id="wpi-quick-submit-tag">I'm done</span>
                </button>
            </div>

            <!-- Action Chips Container (Quick Replies) -->
            <div id="wpi-actions" class="wpi-actions"></div>
            <!-- Input Area -->
            <div class="wpi-input-bar">
                <input type="text" id="wpi-user-input" placeholder="Type WhatsApp, Email or question..." autocomplete="off">
                <button type="button" id="wpi-send-btn" aria-label="Send">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>

            <div class="wpi-footer-note" id="wpi-footer-note">Secured & Direct to Manufacturer</div>
        </div>
    </div>
    <?php
});
