<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPI_Updater {
    const DEFAULT_UPDATE_URL = 'https://raw.githubusercontent.com/GGdm01/wp-inquiry-chatbot/main/info.json';
    const DEFAULT_TOKEN      = '';
    const CACHE_KEY          = 'wpi_update_info_cache';
    const CACHE_TTL          = 21600; // 6 hours

    public static function init() {
        add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'check_update']);
        add_filter('plugins_api', [__CLASS__, 'plugin_info'], 20, 3);
        add_filter('http_request_args', [__CLASS__, 'inject_auth_headers'], 10, 2);
        add_filter('upgrader_post_install', [__CLASS__, 'post_install'], 10, 3);
        add_action('admin_init', [__CLASS__, 'handle_manual_check']);
    }

    public static function get_update_url() {
        if (defined('WPI_UPDATE_URL') && WPI_UPDATE_URL) {
            return WPI_UPDATE_URL;
        }
        return get_option('wpi_update_url', self::DEFAULT_UPDATE_URL);
    }

    public static function get_token() {
        if (defined('WPI_UPDATE_TOKEN') && WPI_UPDATE_TOKEN) {
            return WPI_UPDATE_TOKEN;
        }
        return get_option('wpi_update_token', self::DEFAULT_TOKEN);
    }

    /**
     * Fetch remote version metadata from Gitea / custom JSON
     */
    public static function get_remote_info($force = false) {
        if (!$force) {
            $cached = get_transient(self::CACHE_KEY);
            if ($cached !== false && is_object($cached)) {
                return $cached;
            }
        }

        $url   = self::get_update_url();
        $token = self::get_token();

        if (empty($url)) {
            return false;
        }

        $headers = [
            'Accept' => 'application/json',
        ];
        if (!empty($token)) {
            $headers['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get($url, [
            'timeout'   => 20,
            'headers'   => $headers,
            'sslverify' => false,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (!is_object($data) || empty($data->version) || empty($data->download_url)) {
            return false;
        }

        set_transient(self::CACHE_KEY, $data, self::CACHE_TTL);
        return $data;
    }

    /**
     * Hook into WordPress plugin update check transient
     */
    public static function check_update($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        // Force fresh check when user is on update-core.php or plugins page
        $is_admin_check = is_admin() && (
            (isset($_GET['force-check']) && $_GET['force-check'] == '1') ||
            (isset($GLOBALS['pagenow']) && in_array($GLOBALS['pagenow'], ['update-core.php', 'plugins.php'], true))
        );

        $remote = self::get_remote_info($is_admin_check);
        if (!$remote) {
            return $transient;
        }

        $plugin_file = plugin_basename(WPI_PLUGIN_FILE);

        if (version_compare($remote->version, WPI_VERSION, '>')) {
            $update_item = (object) [
                'id'            => $plugin_file,
                'slug'          => 'wp-inquiry-chatbot',
                'plugin'        => $plugin_file,
                'new_version'   => $remote->version,
                'url'           => $remote->homepage ?? 'https://github.com/GGdm01/wp-inquiry-chatbot',
                'package'       => $remote->download_url,
                'icons'         => [],
                'banners'       => [],
                'tested'        => $remote->tested ?? '6.7',
                'requires_php'  => $remote->requires_php ?? '7.4',
                'compatibility' => new stdClass(),
            ];
            $transient->response[$plugin_file] = $update_item;
            unset($transient->no_update[$plugin_file]);
        } else {
            $no_update_item = (object) [
                'id'            => $plugin_file,
                'slug'          => 'wp-inquiry-chatbot',
                'plugin'        => $plugin_file,
                'new_version'   => WPI_VERSION,
                'url'           => $remote->homepage ?? 'https://github.com/GGdm01/wp-inquiry-chatbot',
                'package'       => '',
                'icons'         => [],
                'banners'       => [],
                'tested'        => $remote->tested ?? '6.7',
                'requires_php'  => $remote->requires_php ?? '7.4',
                'compatibility' => new stdClass(),
            ];
            $transient->no_update[$plugin_file] = $no_update_item;
            unset($transient->response[$plugin_file]);
        }

        return $transient;
    }

    /**
     * Provide rich details modal when clicking "View version details" in WP Admin
     */
    public static function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== 'wp-inquiry-chatbot') {
            return $result;
        }

        $remote = self::get_remote_info(true);
        if (!$remote) {
            return $result;
        }

        $info = (object) [
            'name'           => $remote->name ?? 'WP Inquiry Chatbot',
            'slug'           => 'wp-inquiry-chatbot',
            'version'        => $remote->version,
            'author'         => sprintf('<a href="%s" target="_blank">%s</a>', esc_url($remote->author_profile ?? 'https://github.com/GGdm01'), esc_html($remote->author ?? 'GGdm01')),
            'author_profile' => $remote->author_profile ?? 'https://github.com/GGdm01',
            'homepage'       => $remote->homepage ?? 'https://github.com/GGdm01/wp-inquiry-chatbot',
            'requires'       => $remote->requires ?? '5.6',
            'tested'         => $remote->tested ?? '6.7',
            'requires_php'   => $remote->requires_php ?? '7.4',
            'last_updated'   => $remote->last_updated ?? '',
            'sections'       => [
                'description' => $remote->sections->description ?? '',
                'changelog'   => $remote->sections->changelog ?? '',
            ],
            'download_link'  => $remote->download_url,
        ];

        return $info;
    }

    /**
     * Inject Gitea access token into HTTP requests when downloading the update package zip
     */
    public static function inject_auth_headers($args, $url) {
        $token = self::get_token();
        if (empty($token)) {
            return $args;
        }
        $update_host = wp_parse_url(self::get_update_url(), PHP_URL_HOST);
        $parsed_host = wp_parse_url($url, PHP_URL_HOST);

        if ($parsed_host && ($parsed_host === $update_host || strpos($parsed_host, 'github') !== false || strpos($parsed_host, 'gitea') !== false)) {
            if (empty($args['headers'])) {
                $args['headers'] = [];
            }
            $args['headers']['Authorization'] = 'token ' . $token;
            $args['sslverify'] = false;
        }

        return $args;
    }

    /**
     * Clean up folder destination name after unzip.
     * Gitea archives extract to "wp-chatbot" (repo name), but WP expects "wp-inquiry-chatbot"
     */
    public static function post_install($true, $hook_extra, $result) {
        global $wp_filesystem;

        $my_plugin = plugin_basename(WPI_PLUGIN_FILE);
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $my_plugin) {
            return $true;
        }

        $proper_folder      = dirname($my_plugin);
        $proper_destination = WP_PLUGIN_DIR . '/' . $proper_folder;

        // If the unzipped directory is different (e.g. wp-chatbot vs wp-inquiry-chatbot), move to proper folder
        if ($result['destination'] !== $proper_destination) {
            if ($wp_filesystem->exists($proper_destination)) {
                $wp_filesystem->delete($proper_destination, true);
            }
            $wp_filesystem->move($result['destination'], $proper_destination, true);
            $result['destination']      = $proper_destination;
            $result['destination_name'] = $proper_folder;
        }

        // Keep active
        activate_plugin($my_plugin);
        return $result;
    }

    /**
     * Manual 1-click update check trigger from WP Admin
     */
    public static function handle_manual_check() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        if (isset($_GET['wpi_check_update']) && check_admin_referer('wpi_check_update_action')) {
            delete_transient(self::CACHE_KEY);
            delete_site_transient('update_plugins');

            wp_safe_redirect(admin_url('update-core.php?force-check=1'));
            exit;
        }
    }
}
