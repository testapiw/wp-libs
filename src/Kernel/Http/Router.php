<?php

namespace WpLibs\Kernel\Http;

use WP_REST_Request;
use WP_REST_Response;
use Closure;
use WpLibs\Kernel\Http\BaseController;


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
        $this->controllers = [];

        // Hook into WordPress REST API initialization to register routes
        add_action('rest_api_init', [$this, 'register_routes']);
 
    }

    /**
     * Register all routes for all controllers
     */
    public function register_routes()
    {
        $this->controllers = apply_filters('wplibs_register_controllers', []);

        foreach ($this->controllers as $controller) {

            if (!$controller instanceof BaseController) continue;

            foreach ($controller->get_routes() as $route) {
                register_rest_route(
                    $route['namespace'],
                    $route['route'],
                    [
                        'methods'             => $route['methods'],
                        'callback'            => $this->wrap_callback($controller, $route['callback']),
                        'permission_callback' => $route['permission_callback'] ?? [$controller, 'check_permissions'],
                        'show_in_index'       => $route['show_in_index'] ?? false,
                        'args'                => $route['args'] ?? [],
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
