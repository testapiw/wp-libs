<?php

namespace App\Kernel\Http;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Psr\Log\LoggerInterface;
use App\Kernel\LoggerAdapter;
use App\Kernel\Exceptions\ExceptionHandler;
use App\Kernel\Exceptions\ApplicationException;


abstract class BaseController
{
    // Logger instance for this controller
    private LoggerInterface $logger;

    /**
     * BaseController constructor
     * 
     * @param LoggerInterface|null $logger Optional logger; uses default LoggerAdapter if null
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new LoggerAdapter();
    }

    /**
     * Get the logger instance
     * 
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Check if the current request has valid permissions
     * 
     * @param WP_REST_Request $request
     * @return true|WP_Error Returns true if allowed, WP_Error if forbidden
     */
    public function check_permissions(WP_REST_Request $request)
    {
        // Verify nonce for security
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', __('Invalid nonce', 'text-domain'), ['status' => 403]);
        }
            
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('User not logged in', 'text-domain'), ['status' => 403]);
        }

        // Check if user has general admin capabilities
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', __('Insufficient permissions', 'text-domain'), ['status' => 403]);
        }

        // Restrict access to specific roles
        $user = wp_get_current_user();
        $allowed_roles = ['administrator', 'manager'];
        if (!array_intersect($allowed_roles, (array) $user->roles)) {
            return new WP_Error('rest_forbidden', __('User role not allowed', 'text-domain'), ['status' => 403]);
        }

        return true; // All checks passed
    }


    /**
     * Standardized request handler
     * Wraps controller logic with try-catch for consistent error handling
     * 
     * @param callable $callback
     * @return mixed|WP_REST_Response
     */
    public function handle(callable $callback)
    {
        try {
            return $callback();
        } 
        catch (ApplicationException $e) {
            // Handle known application exceptions
            ExceptionHandler::handle($e);
            return $this->handlerApplicationError($e);
        } 
        catch (\Throwable $e) {
            // Handle unexpected system errors
            ExceptionHandler::handle($e);
            return $this->handlerSystemError($e);
        }
    }


    /**
     * Get all routes defined in the controller
     * Must be implemented in child controllers
     * 
     * @return array
     */
    abstract public function get_routes(): array;

    /**
     * Handle known application-level errors
     * 
     * @param ApplicationException $e
     * @return WP_REST_Response
     */
    function handlerApplicationError(ApplicationException $e): WP_REST_Response
    {
        /* Optional logging for warnings
        $this->logger->warning($e->getMessage(), [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);*/

        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
        ], 400);    // HTTP 400 Bad Request
    }

    /**
     * Handle unexpected system errors
     * 
     * @param \Throwable $e
     * @return WP_REST_Response
     */
    function handlerSystemError(\Throwable $e): WP_REST_Response
    {
         // Log full error details
        $this->logger->error($e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

        return new WP_REST_Response([
            'success' => false,
            'message' => 'Internal server error',
            'details' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null, // Show details only in debug mode
        ], 500); // HTTP 500 Internal Server Error 
    }
}
