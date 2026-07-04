<?php

namespace WpLibs;

class RequestDetect {

    private static ?string $rest_prefix = null;
    private static string $current_uri = '';
    private static string $rest_param = '';

    public static function detect() {
        if (defined('WPLIBS_IS_ADMIN')) {
            return;
        }

        self::$current_uri = $_SERVER['REQUEST_URI'] ?? '';
        self::$rest_param = $_GET['rest_route'] ?? '';

        self::$rest_prefix = trim(rest_get_url_prefix(), '/'); // wp-json
        $rest_p = '/' . self::$rest_prefix . '/';
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        
        $is_rest = (strpos(self::$current_uri, $rest_p) !== false) || !empty(self::$rest_param);
        $is_ajax = (strpos($script_name, 'admin-ajax.php') !== false) || defined('DOING_AJAX');
        $is_cron = (strpos($script_name, 'wp-cron.php') !== false) || defined('DOING_CRON');
        
        $is_admin = is_admin() && !$is_ajax && !$is_cron;
        $is_front = !$is_admin && !$is_ajax && !$is_cron && !$is_rest;

        define('WPLIBS_IS_REST', $is_rest);
        define('WPLIBS_IS_ADMIN', $is_admin);
        define('WPLIBS_IS_AJAX', $is_ajax);
        define('WPLIBS_IS_CRON', $is_cron);
        define('WPLIBS_IS_FRONT', $is_front);
    }

    public static function isApiNamespace(string $namespace): bool
    {
        if (self::$rest_prefix === null) {
            self::detect();
        }

        if (!defined('WPLIBS_IS_REST') || !WPLIBS_IS_REST) {
            return false;
        }

        $namespace = '/' . trim($namespace, '/'); // "/api/v1"
        
        // ?rest_route=/api/v1
        $is_plain = strpos(self::$rest_param, $namespace) !== false;
        
        // "/wp-json/api/v1"
        $is_pretty = strpos(self::$current_uri, $namespace) !== false;
        return $is_pretty || $is_plain;
    }
}