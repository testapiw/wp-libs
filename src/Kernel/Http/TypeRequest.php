<?php

namespace WpLibs\Kernel\Http;

class TypeRequest
{
    private static $type = '';
    
    public static function classifyRequest()
    {
        if (self::$type) {
            return self::$type;
        }

        if (wp_doing_ajax()) {
            self::$type = 'AJAX';
        } elseif (wp_is_json_request()) {
            self::$type = 'JSON';
        } elseif (wp_is_serving_rest_request()) {
            self::$type = 'REST';
        } elseif (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            self::$type = 'XMLRPC';
        } elseif (self::isXmlRelatedRequest()) {
            self::$type = 'XMLRPC';
        } else {
            self::$type = 'DEFAULT';
        }

        return self::$type;
    }

    private static function isXmlRelatedRequest()
    {
        global $wp_query;

        return wp_is_xml_request()
            || (isset($wp_query) && (
                (function_exists('is_feed') && is_feed())
                || (function_exists('is_comment_feed') && is_comment_feed())
                || (function_exists('is_trackback') && is_trackback())
            ));
    }
}