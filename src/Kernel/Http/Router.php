<?php

namespace App\Kernel\Http;

use WP_REST_Request;
use WP_REST_Response;
use Closure;
use App\Kernel\Http\BaseController;
use App\Controllers\AppController;


class Router
{
    // Array to hold controller instances
    protected array $controllers = [];

    /**
     * Router constructor
     * Initializes the router and sets up controllers for the REST API
     */
    public function __construct()
    {
        // Get the current request URI
        $route = $_SERVER['REQUEST_URI'] ?? '';

        // Only proceed if the request is for our custom API namespace
        if (strpos($route, '/wp-json/api/v1') === false) {
            return;
        }
        
        // Initialize default controllers
        $this->controllers = [
            new AppController(),
           
        ];

        // Optionally add admin-only controllers if the user is an administrator
        if (current_user_can('administrator')) {
           // $this->controllers[] = new NotifierController();
           // $this->controllers[] = new TransportController();
        }

        // Hook into WordPress REST API initialization to register routes
        add_action('rest_api_init', [$this, 'register_routes']);
 
        /**
         * TODO: Dependency Injection (DI) version
         * Automatically load all controller classes in Admin folder
         * $classes = glob(__DIR__ . '/../Controllers/Admin/*Controller.php');
         * $this->controllers = array_map(fn($file) => new (get_class_from_file($file))(), $classes);
         */
    }

    /**
     * Register all routes for all controllers
     */
    public function register_routes()
    {
        foreach ($this->controllers as $controller) {
            foreach ($controller->get_routes() as $route) {
                register_rest_route(
                    $route['namespace'], // API namespace
                    $route['route'],     // Route path
                    [
                        'methods'             => $route['methods'], // HTTP method
                        'callback'            => $this->wrap_callback($controller, $route['callback']), // Wrap callback
                        'permission_callback' => $route['permission_callback'] ?? [$controller, 'check_permissions'], // Permissions check
                        'show_in_index'       => $route['show_in_index'] ?? false, // Show in REST API index
                        'args'                => $route['args'] ?? [], // Route arguments
                    ]
                );
            }
        }
    }

    /**
     * Wrap the controller callback to include pre- and post-handling
     * 
     * @param BaseController $controller
     * @param callable $callback
     * @return callable
     */
    protected function wrap_callback(BaseController $controller, callable $callback): callable
    {
        // Return a closure that wraps the original callback
        return function (WP_REST_Request $request) use ($controller, $callback) {
            // Use the controller's handle method for standardized error handling / processing
            return $controller->handle(function () use ($callback, $request) {
                // Call the actual callback function with the request
                return call_user_func($callback, $request);
            });
        };
    }
}
