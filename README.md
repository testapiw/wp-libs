# wp-libs – WordPress Extension Library with Vue.js, Bootstrap, and DDD Backend

`wp-libs` is a comprehensive WordPress extension library that provides a modern frontend stack with **native Vue.js and Bootstrap** integration, and a robust backend architecture following **Domain-Driven Design (DDD)** principles. It includes a lightweight REST client for seamless API interactions and a structured PHP framework for building scalable WordPress plugins and themes.

The library consists of:
- **Frontend**: Vue.js components with Bootstrap styling for native WordPress admin interfaces.
- **Backend**: DDD-inspired PHP architecture with controllers, services, and domain logic.
- **API Layer**: RESTful endpoints with automatic nonce handling and error management.

---

## Features

- **Frontend Integration**: Native Vue.js 3 with Bootstrap 5 for responsive, modern UIs in WordPress admin.
- **Backend Architecture**: Domain-Driven Design with layered architecture (Controllers, Services, Repositories).
- **REST API Client**: Lightweight JavaScript wrapper for WordPress REST API with automatic nonce support.
- **Error Handling**: Unified exception handling and logging (PSR-3 compatible).
- **Security**: Built-in permission checks, role-based access, and nonce validation.
- **Zero Dependencies**: Minimal footprint, easy to integrate into existing WordPress projects.

---

## REST API Request Flow

```
Browser (Vue.js)  →  XTRequest (fetch + nonce)  →  WP REST API (api/v1/*)
                                                          │
                                                          ▼
                                              Router::wrap_callback()
                                                          │
                                                          ▼
                                              BaseController::handle()
                                                          │
                                                          ▼
                                              Controller method (e.g. get_deals)
                                                          │
                                                          ▼
                                              Service (validator → builder → repository)
                                                          │
                                                          ▼
                                              AbstractQuery (SQL) → wpdb
```

---

## Installation

### 1. Install the library

Place the `wp-libs` directory inside `wp-content/` (next to `plugins/` and `themes/`), so the path is `wp-content/wp-libs/`.

### 2. Load the autoloader via a must-use plugin

Create `wp-content/mu-plugins/wp-libs.php`:

```php
<?php
/**
 * Plugin Name: wp-libs Loader
 * Description: Loads the wp-libs library and boots it.
 */

require_once WP_CONTENT_DIR . '/wp-libs/vendor/autoload.php';

add_action( 'plugins_loaded', function () {
    new WpLibs\Bootstrap();
} );
```

The autoloader maps the `WpLibs\` namespace to `src/` and defines the `LIBS_DIR` constant. `Bootstrap` defines `WPLIBS_DIR`, `WPLIBS_URL` and `WPLIBS_VER`, detects the request type, and fires the `on_admin` / `on_front` / `on_ajax` / `on_cron` / `on_rest` actions.

### 3. Register REST controllers

Controllers are registered through the `wplibs_register_controllers` filter. The `Router` wraps every controller callback with `BaseController::handle()`, so controller methods must **not** call `handle()` themselves.

```php
add_filter( 'wplibs_register_controllers', function ( $controllers ) {
    $controllers[] = new MyPlugin\MyController();
    return $controllers;
} );
```

### 4. Enqueue frontend assets

The library ships a REST client and two Vue components (pagination + dropdown). Enqueue them from `WPLIBS_URL`:

```php
// REST client (window.vcom.XTRequest)
wp_enqueue_script( 'request-libs', WPLIBS_URL . 'assets/js/request.js', [], WPLIBS_VER, true );

// Vue components (window.wplibs.getPagination / getDropdown)
wp_enqueue_script( 'wplibs-pagination-js', WPLIBS_URL . 'assets/js/pagination.js', [ 'vue-js' ], WPLIBS_VER, true );
wp_enqueue_script( 'wplibs-dropdown-js',  WPLIBS_URL . 'assets/js/dropdown.js',  [ 'vue-js' ], WPLIBS_VER, true );
```

### 5. Client-side initialization

```js
// REST API Client
const Request = new window.vcom.XTRequest({
    base_url: wpApiSettings.root, // WordPress REST API base
    rest_nonce: wpApiSettings.nonce
});

// Vue.js app
const app = Vue.createApp({ /* your components */ });
app.mount('#wp-libs-app');
```


## Passing Data from PHP to JavaScript

You can use `wp_localize_script` to provide REST URLs, nonces, and configuration to your JavaScript/Vue code.

### Example Localization
```php
wp_localize_script( 'my-plugin-js', 'myPluginConfig', [
    'api_url' => rest_url( 'api/v1/deals/list' ),
    'nonce'   => wp_create_nonce( 'wp_rest' ),
] );
```

### Client-side API Initialization
```js
const Request = new window.vcom.XTRequest({
    base_url: myPluginConfig.api_url,
    rest_nonce: myPluginConfig.nonce
});
```

## Vue.js and Bootstrap Integration

wp-libs supports native Vue.js 3 components with Bootstrap 5 styling for building modern WordPress admin interfaces. Components are defined in JavaScript and can use external templates or inline.

The library ships two ready-made Vue components:

- **Pagination** — `window.wplibs.getPagination()` — renders a Bootstrap pagination bar.
- **Dropdown** — `window.wplibs.getDropdown()` — renders a Bootstrap dropdown filter.

Register them in your app and print their templates in the admin footer:

```js
const app = Vue.createApp({ /* ... */ });
app.component( 'pagination', window.wplibs.getPagination() );
app.component( 'dropdown',   window.wplibs.getDropdown() );
```

```php
// Print the component templates in the admin footer.
add_action( 'admin_footer', function () {
    echo \WpLibs\Kernel\Utils\Templates::render( 'part/pagination' );
    echo \WpLibs\Kernel\Utils\Templates::render( 'part/dropdown' );
} );
```

## Domain-Driven Design (DDD) Backend

The PHP backend follows DDD principles with a layered architecture:

- **Domain Layer**: Business logic and entities (e.g., `DealDefinition`, `QueryObject`).
- **Application Layer**: Services and use cases (e.g., `DealsService`).
- **Infrastructure Layer**: Controllers, repositories, and external integrations.
- **Presentation Layer**: REST API endpoints with `BaseController`.

### Query pipeline

For list endpoints the library provides a clean **validator → builder → repository** flow:

```
Controller → Service → QueryValidator → DealsQueryBuilder → DealsRepository → AbstractQuery → wpdb
```

1. **`QueryValidator`** (`WpLibs\Kernel\Queries\QueryValidator`) — validates and sanitizes raw request params (`page`, `per_page`, `sort_column`, `sort_order`, `filters`) against a `DefinitionInterface` schema. It derives filterable fields from `fields()`, sanitizes values by field type, and truncates strings to `max_length`.
2. **Query builder** — converts the validated params into a `QueryObject` (filters, order, pagination).
3. **`DealsRepository`** — executes the query via `AbstractQuery` (`runSelect()`, `runCount()`).
4. **`AbstractQuery`** (`WpLibs\Kernel\Queries\AbstractQuery`) — extends `BaseRepository`, assembles SQL from a `QueryObject` using `QueryTrait`.

### Defining a table schema

```php
use WpLibs\Kernel\Contracts\DefinitionInterface;

class DealDefinition implements DefinitionInterface
{
    public static function fields(): array
    {
        return [
            'id'          => [ 'type' => 'int',    'column' => 'id' ],
            'deal_number' => [ 'type' => 'string', 'column' => 'deal_number', 'max_length' => 20 ],
            'client'      => [ 'type' => 'string', 'column' => 'client',      'max_length' => 100 ],
            'status'      => [ 'type' => 'string', 'column' => 'status',      'max_length' => 20 ],
            'amount'      => [ 'type' => 'float',  'column' => 'amount' ],
            'created_at'  => [ 'type' => 'string', 'column' => 'created_at',  'max_length' => 19 ],
        ];
    }

    public static function sortable(): array
    {
        return self::fields();
    }

    public static function defaultSortable(): ?string
    {
        return 'created_at';
    }
}
```

### Controller Integration
```php
use WpLibs\Kernel\Http\BaseController;

class DealsController extends BaseController
{
    private DealsService $service;

    public function __construct( ?LoggerInterface $logger = null )
    {
        parent::__construct( $logger );
        $this->service = new DealsService( $logger );
    }

    public function get_routes(): array
    {
        return [
            [
                'namespace' => 'api/v1',
                'route'     => '/deals/list',
                'methods'   => 'POST',
                'callback'  => [ $this, 'get_deals' ],
                'show_in_index' => false,
            ],
        ];
    }

    public function get_deals( WP_REST_Request $request ): WP_REST_Response
    {
        $params = $request->get_params();
        $data   = $this->service->getDeals( $params );

        return new WP_REST_Response( array_merge( [ 'success' => true ], $data ), 200 );
    }
}
```

> **Note:** The `Router` wraps every controller callback with `BaseController::handle()`. Controller methods must **not** call `handle()` themselves.

This structure ensures scalability, testability, and separation of concerns.

## Constructor Options

| Option      | Type   | Description                  |
|------------|--------|------------------------------|
| base_url   | string | Base REST API URL            |
| ajax_url   | string | (optional) admin-ajax.php URL |
| nonce      | string | Custom nonce                |
| rest_nonce | string | WordPress REST nonce        |


## Available Methods

```js
Request.get(endpoint, payload)
Request.post(endpoint, payload)
Request.put(endpoint, payload)
Request.delete(endpoint, payload)
```
All methods return a Promise.

## Usage Examples

### Login

```js
function logIn(username, password) {
    return Request.post('login', { username, password });
}

logIn('admin', 'password')
    .then(response => {
        if (response?.status === 'success') {
            console.log('Login successful');
        }
    });
```

```js
function signUp(data) {
    return Request.post('signup', data);
}

signUp(validator.getFormData())
    .then(response => {
        if (response?.status === 'success') {
            console.log('Signup successful');
        }
    });
```

## GET request

```js
Request.get('filters')
    .then(response => {
        if (response.success) {
            console.log(response.results);
        }
    });
```

## Response Format

### Success response

```js
{
  "success": true,
  "status": "success",
  "results": {}
}
```

### Error response

```js
{
  "success": false,
  "message": "Server error",
  "status": 500
}
```

## Server-side REST API Example

```php
register_rest_route('auth/v1', '/login', [
        'methods'  => 'POST',
        'callback' => function (WP_REST_Request $request) {

            $username = $request->get_param('username');
            $password = $request->get_param('password');

            if (!$username || !$password) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Missing credentials'
                ], 400);
            }

            return [
                'success' => true,
                'status'  => 'success',
                'results' => [
                    'user' => $username
                ]
            ];
        },
        'permission_callback' => '__return_true',
    ]);

```

## Notes

- **Frontend**: Vue.js components are designed for WordPress admin, ensuring compatibility with wp-admin styles.
- **Backend**: DDD structure promotes clean code; extend services and controllers as needed.
- **API**: All requests use JSON; nonces are handled automatically for security.
- **Extensibility**: Easily add new Vue components, Bootstrap themes, or DDD domains.
- **Performance**: Lazy-load scripts and use caching for optimal WordPress performance.


## Documentation

- [Server-side REST API Architecture](SERVER_API.md) - Detailed documentation of the PHP backend API structure, DDD layers, and routing.
- [Vue.js Integration Guide](https://vuejs.org/guide/) - Official Vue.js documentation for component development.
- [Bootstrap Components](https://getbootstrap.com/docs/5.0/components/) - Bootstrap 5 component library for styling.


## License

MIT

- [Changelog](CHANGELOG.md) - Recent updates and changes.
