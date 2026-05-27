<?php

namespace WpLibs;

class RequestDetect {

    public static function detect() {
        if (!defined('BS_IS_ADMIN')) {
            $is_admin = is_admin();
            $rest_p = trailingslashit(rest_get_url_prefix());
            $is_rest = (false !== strpos($_SERVER['REQUEST_URI'], $rest_p));
            define('BS_IS_REST', $is_rest);
            define('BS_IS_ADMIN', $is_admin);
            define('BS_IS_FRONT', !$is_admin && !defined('DOING_AJAX') && !$is_rest);
            define('BS_IS_AJAX', defined('DOING_AJAX') && DOING_AJAX);
            define('BS_IS_CRON', defined('DOING_CRON') && DOING_CRON);
        }
    }

}