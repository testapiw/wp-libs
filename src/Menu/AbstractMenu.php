<?php

namespace WpLibs\Menu;

use WpLibs\Kernel\Utils\Templates;

abstract class AbstractMenu
{
    protected string $template_name = '';
    protected string $template_path = '';
    protected array $screens = [];
    protected $currentScreen = null;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        add_action('admin_menu', [$this, 'add_menu_page'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        $this->bootstrap();
    }

    protected function bootstrap(): void {}
    

    abstract public function add_menu_page();

    public function render_page()
    {
        echo Templates::render($this->template_name);
    }

    public function enqueue_assets($hook)
    {
        if ($this->isCurrentScreen()) {
            $this->setCurrentScreen($hook);
            $this->enqueue_common_assets();
            $this->assets();
        }
    }

    function getCurrentScreen(): ?string
    {
        return $this->currentScreen;
    }
    
    private function setCurrentScreen(string $hook): void
    {
        $this->currentScreen = null;
        if (isset($this->screens[$hook])) {
           $this->currentScreen = $this->screens[$hook];
        }
    }

    protected function assets(){}

    protected function enqueue_common_assets()
    {
        wp_enqueue_style('wplibs-bootstrap-css', WPLIBS_URL . 'assets/css/libs/bootstrap/bootstrap.min.css');
        wp_enqueue_script('wplibs-bootstrap-js', WPLIBS_URL . 'assets/js/libs/bootstrap/bootstrap.bundle.min.js', ['jquery'], null, true);

        // wp_enqueue_script('vue-js', 'https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js', ['jquery'], null, true);
        wp_enqueue_script('vue-js', WPLIBS_URL . 'assets/js/libs/vue.global.prod.js', [], '3.2.47', true);
        wp_enqueue_script('request-libs', WPLIBS_URL . 'assets/js/libs/request.js', [], WPLIBS_VER, true);
    }

    protected function isCurrentScreen(): bool
    {
        $screen = get_current_screen();
        return ($screen && in_array($screen->id, $this->getScreenKeys(), true));
    }

    private function getScreenKeys()
    {
        return array_keys($this->screens);
    }

    protected function setTemplatePath(string $path) 
    {
        $this->template_path = ltrim($path, '/');
        return $this;
    }

    protected function render(string $name, array $data = []) 
    {
        $file = $this->template_path . '/'. ltrim($name, '/'). '.php';
        
        if (file_exists($file)) {
            ob_start();
            require $file; 
            return ob_get_clean();  
        }
        return '----'; 
    }
}
