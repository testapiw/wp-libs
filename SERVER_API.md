# Server-side REST API Architecture (WordPress)

This document describes the **server-side REST API architecture** used in the project.  
The system is built on top of the **WordPress REST API**, with a custom controller layer, centralized routing, permission checks, and unified error handling.

---

## Table of Contents

- [Overview](#overview)
- [REST API Request Flow](#rest-api-request-flow)
- [Key Components](#key-components)
- [Flow Summary](#flow-summary)
- [BaseController](#basecontroller)
- [Router](#router)
- [Example: AppController](#example-appcontroller)
- [Validation via ApplicationException](#validation-via-applicationexception)
- [Expected Request Headers](#expected-request-headers)
- [Standard Success Response](#standard-success-response)
- [Best Practices Used](#best-practices-used)
- [Related Documentation](#related-documentation)
- [License](#license)

---

## Overview

The REST API layer consists of:

- `BaseController` — abstract base class for all REST controllers
- `Router` — centralized REST route registrar
- Domain controllers (`AppController`)
- Unified exception & error handling
- Role-based access control

---


# REST API Request Flow

```mermaid
flowchart TD
    A[Client / Frontend] -->|HTTP Request (GET / POST / etc.)| B[WordPress REST API Endpoint]
    B -->|Matches URL /wp-json/api/v1/...| C[Router]
    C --> D[Controller (AppController)]
    D --> E[BaseController]
    E --> F[Callback Logic in AppController]
    F --> G[Response Handling]
    G --> A

    subgraph RouterDetails [Router]
        C1[Checks if request URI belongs to custom API]
        C2[Initializes controller instances (e.g., AppController)]
        C3[Registers routes via register_rest_route()]
    end
    C --> RouterDetails

    subgraph BaseControllerDetails [BaseController]
        E1[Wraps callbacks in handle() method]
        E2[Centralized error handling: ApplicationException -> handlerApplicationError(), Throwable -> handlerSystemError()]
        E3[Permission checks via check_permissions()]
    end
    E --> BaseControllerDetails

    subgraph CallbackDetails [Callback Logic]
        F1[Actual business logic (e.g., fetching data, updating)]
        F2[Returns WP_REST_Response with data / success status]
    end
    F --> CallbackDetails

    subgraph ResponseHandling [Response Handling]
        G1[If exception occurs: ExceptionHandler::handle(), dispatches ExceptionEvent, logs via LoggerAdapter]
        G2[If no exception: Returns WP_REST_Response to WordPress, JSON response sent to client]
    end
    G --> ResponseHandling
```

## Key Components

| Component              | Responsibility                                                                 |
|------------------------|-------------------------------------------------------------------------------|
| Router                 | Registers API routes, wraps callbacks for error handling                     |
| BaseController         | Abstract base, handles permissions, logging, centralized error handling      |
| AppController          | Implements actual API endpoints and business logic                            |
| ApplicationException   | Predictable application errors with custom codes and log levels               |
| ExceptionHandler       | Global exception handler; dispatches events, logs errors                      |
| LoggerAdapter          | Provides PSR-3 compatible logging for errors                                  |
| WP_REST_Response       | Standard WordPress REST response object                                       |
| Client / Frontend      | Sends HTTP requests, receives JSON responses     


## Flow Summary

1. Client sends an HTTP request to a registered REST API route.
2. WordPress routes it to your **Router**.
3. **Router** delegates to the correct controller (e.g., **AppController**).
4. **BaseController** wraps the callback in `handle()`, checking permissions and handling errors.
5. Controller executes business logic and returns a `WP_REST_Response`.
6. If an exception occurs, **ExceptionHandler** logs it and optionally dispatches events.
7. JSON response is sent back to the client.


## BaseController

`BaseController` is the foundation for all REST controllers.

### Responsibilities

- Permission checking (nonce, auth, roles)
- Centralized exception handling
- Logging (PSR-3 compatible)
- Standardized REST responses

### Location

App/Kernel/Http/BaseController.php

### Key methods

#### `check_permissions(WP_REST_Request $request)`
Default permission callback for protected routes.
* **Validates**: `X-WP-Nonce` (`wp_rest`), user auth, and `manage_options` capability.
* **Returns**: `true` on success or `WP_Error` (403) on failure.

### Checks:

- `X-WP-Nonce` validity (`wp_rest`)
- User authentication
- WordPress capability (`manage_options`)
- Allowed roles (`administrator`, `manager`)

```php
public function check_permissions(WP_REST_Request $request)
```


#### `handle(callable $callback)`
Wraps controller logic and catches all exceptions. 

| Exception Type | HTTP Code | Response Description |
| :--- | :--- | :--- |
| `ApplicationException` | 400 | Client-side/Logic error |
| `Throwable` | 500 | Unexpected internal server error |

---

## Error response format

Application error
```json
{
  "success": false,
  "message": "Error message",
  "code": 1001
}
```

```json
{
  "success": false,
  "message": "Internal server error",
  "details": "Error message (only if WP_DEBUG=true)"
}
```

## Router

The Router class dynamically registers REST routes from all controllers.

Location

```php
App/Kernel/Http/Router.php
```

## How it works

Checks if current request targets /wp-json/api/v1

Instantiates controller classes

Registers routes from each controller via get_routes()

Wraps callbacks with BaseController::handle()

```php
add_action('rest_api_init', [$this, 'register_routes']);
```

## Route definition format

Each controller returns routes as an array:
```php
[
    'namespace'     => 'api/v1',
    'route'         => '/data/list',
    'methods'       => 'POST',
    'callback'      => [$this, 'get_data_list'],
    'permission_callback' => [$this, 'check_permissions'], // optional
    'show_in_index' => false,
    'args'          => [],
]
```

If permission_callback is not specified, BaseController::check_permissions() is used by default.

## Example: AppController
Location

```php
App/Controllers/AppController.php
```

### Routes defined in AppController

```php
public function get_routes(): array
{
    return [
        [
            'namespace' => 'api/v1',
            'route'     => '/data/list',
            'methods'   => 'POST',
            'callback'  => [$this, 'get_data_list'],
            'show_in_index' => false,
        ],
        [
            'namespace' => 'api/v1',
            'route'     => '/data/edit',
            'methods'   => 'POST',
            'callback'  => [$this, 'data_edit'],
            'show_in_index' => false,
        ],
        [
            'namespace' => 'api/v1',
            'route'     => '/data/analytics',
            'methods'   => 'GET',
            'callback'  => [$this, 'get_analytics'],
            'show_in_index' => false,
        ],
        [
            'namespace' => 'api/v1',
            'route'     => '/data/log',
            'methods'   => 'POST',
            'callback'  => [$this, 'get_data_log'],
            'show_in_index' => false,
        ],
    ];
}
```

## Example handler
```php
public function get_data_list(WP_REST_Request $request): WP_REST_Response
{
    $params = $request->get_params();

    $results = []; // Business logic here

    return new WP_REST_Response([
        'success' => true,
        'data' => $results
    ], 200);
}
```

Another example:
```php
public function get_analytics(WP_REST_Request $request): WP_REST_Response
{
    $analytics = new AnalyticService(); // Assuming service exists
    $results = $analytics->getUpdateDate();

    return new WP_REST_Response([
        'success' => true,
        'data' => [
            'update' => $results,
        ]
    ], 200);
}
```

## Validation via ApplicationException
```php
if (!is_numeric($index) || (int)$index <= 0) {
    throw new ApplicationException(
        'Missing index ID',
        ApplicationException::MISSING_ID
    );
}
```

## Automatically converted to a 400 REST response.

## Expected Request Headers

All protected routes expect:

Content-Type: application/json
X-WP-Nonce: <wp_rest_nonce>

Standard Success Response
```json
{
  "success": true,
  "data": {}
}
```

## Best Practices Used

- Centralized routing
- Strict permission checks
- Role-based access
- PSR-3 logging
- Clear separation of concerns
- Predictable JSON responses
- REST namespace versioning


## Related Documentation

- Client-side REST client: Request (JavaScript)
- Controllers: App/Controllers/*
- Kernel HTTP layer: App/Kernel/Http


## License

MIT