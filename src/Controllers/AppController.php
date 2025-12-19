<?php

namespace App\Controllers;

use App\Kernel\Filter\QueryFilter;
use App\Kernel\Filter\UpdateFilter;

use WP_REST_Request;
use WP_REST_Response;
use App\Kernel\Http\BaseController;
use App\Kernel\Exceptions\ApplicationException;


class AppController extends BaseController
{

    /**
     * Define REST API routes for this controller
     * 
     * @return array List of route definitions
     */
    public function get_routes(): array
    {
        return [
            [
                'namespace'     => 'api/v1',                // API namespace version
                'route'         => '/data/list',            // Route URL
                'methods'       => 'POST',                  // HTTP method allowed
                'callback'      => [$this, 'get_data_list'], // Callback function for this route
                'show_in_index' => false,                   // Do not show in WP REST API index

            ],
            [
                'namespace'     => 'api/v1',
                'route'         => '/data/edit',
                'methods'       => 'POST',
                'callback'      => [$this, 'data_edit'],
                'show_in_index' => false, 
            ],
            [
                'namespace'     => 'api/v1',
                'route'         => '/data/analytics',
                'methods'       => 'GET',
                'callback'      => [$this, 'get_analytics'],
                'show_in_index' => false, 
            ],
            [
                'namespace'     => 'api/v1',
                'route'         => '/data/log',
                'methods'       => 'POST',
                'callback'      => [$this, 'get_data_log'],
                'show_in_index' => false, 
            ],
        ];
    }

    /**
     * Handle fetching data logs
     * 
     * @param WP_REST_Request $request The incoming REST request
     * @return WP_REST_Response Response object with logs data
     */
    public function get_data_log(\WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params(); // Get all request parameters
        
        $results = []; // Placeholder for logs data

        return new \WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200); // Return success response with 200 status code
    }

    /**
     * Handle fetching a list of data
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_data_list(\WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params();

        $results = [];

        return new \WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Handle editing data
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function data_edit(\WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params();

        $results = [];

        return new \WP_REST_Response([
            'success' => $results, // Indicates the success of the edit operation
            'data' => []           // Placeholder for any returned data
        ], 200);
    }


    /**
     * Get analytics data (update dates)
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    function get_analytics(\WP_REST_Request $request): WP_REST_Response
    {
        $anaitics = new AnalyticService;
        $results = $anaitics->getUpdateDate();

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'update' => $results,
            ]
        ], 200);  
    }
}