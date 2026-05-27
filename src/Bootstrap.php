<?php

namespace WpLibs;

define('WP_LIBS_DIR', plugin_dir_path(dirname(__FILE__)));
define('WP_LIBS_URL', plugin_dir_url(dirname(__FILE__)));
define('WP_LIBS_VER', '0.0.2');

use WpLibs\Http\Router;
use WpLibs\RequestDetect;
use Docdream\Menu\DocdreamMenu;
use Docdream\Controller\SettingsController;
use StockUpdater\Infrastructure\Persistence\ConfigRepository;

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
        global $wpdb;

        if (defined('BS_IS_ADMIN') && BS_IS_ADMIN) {
            do_action('on_admin');
            add_action('admin_menu', [new DocdreamMenu, 'add_menu_page']);
        }

        if (defined('BS_IS_FRONT') && BS_IS_FRONT) {
            do_action('on_front');
        }

        if (defined('BS_IS_AJAX') && BS_IS_AJAX) {
            do_action('on_ajax');
        }

        if (defined('BS_IS_CRON') && BS_IS_CRON) {
            do_action('on_cron');
        }

        if (defined('BS_IS_REST') && BS_IS_REST) {
            new Router;
            ConfigRepository::init($wpdb);
            add_filter('wplibs_register_controllers', [$this, 'controllers']);
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
            $controllers[] = new SettingsController();            
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
