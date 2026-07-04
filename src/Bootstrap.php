<?php

namespace WpLibs;

define('WPLIBS_DIR', WP_CONTENT_DIR . '/wp-libs/');
define('WPLIBS_URL', WP_CONTENT_URL . '/wp-libs/');
define('WPLIBS_VER', '0.0.2');

use WpLibs\Http\Router;
use WpLibs\RequestDetect;

class Bootstrap
{
    function __construct()
    {
        $this->detectRequest();
        $this->initialize();
        $this->setShutdown();       
    }

    function detectRequest()
    {
        RequestDetect::detect();
    }

    private function initialize() {
        if (defined('WPLIBS_IS_ADMIN') && WPLIBS_IS_ADMIN) {
            do_action('on_admin');
        }

        if (defined('WPLIBS_IS_FRONT') && WPLIBS_IS_FRONT) {
            do_action('on_front');
        }

        if (defined('WPLIBS_IS_AJAX') && WPLIBS_IS_AJAX) {
            do_action('on_ajax');
        }

        if (defined('WPLIBS_IS_CRON') && WPLIBS_IS_CRON) {
            do_action('on_cron');
        }

        if (defined('WPLIBS_IS_REST') && WPLIBS_IS_REST) {
            if (RequestDetect::isApiNamespace('api/v1')) {
                new Router;
                add_filter('wplibs_register_controllers', [$this, 'controllers']);
            }

            do_action('on_rest');
        }

       $this->init();
    }

    public function init()
    {
        do_action('bs_init');
    }


    function controllers($controllers)
    {
        if (current_user_can('manage_options')) {        
        }
        return $controllers;
    }


    function setShutdown()
    {
        register_shutdown_function(function() {
            $lastError = error_get_last(); 
            if ($lastError) {
                // ErrorHandler::handle($lastError);
            }
        });
    }

}
