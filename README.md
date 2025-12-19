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

## Installation

### 1. Install via Composer (Backend)
```bash
composer require wp-plugins/wp-libs
```

### 2. Enqueue Scripts in WordPress (Frontend)
```php
// Enqueue Vue.js and Bootstrap (if not already included)
wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue@3', [], '3.0', true);
wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css', [], '5.0');

// Enqueue wp-libs scripts
wp_enqueue_script('wp-libs-js', LIBS_URL . '/js/request.js', ['vue'], LIBS_VER, true);
wp_enqueue_script('wp-libs-vue', LIBS_URL . '/js/vue-components.js', ['vue', 'wp-libs-js'], LIBS_VER, true); // Assuming Vue components file
```

### 3. Initialize in PHP
```php
use App\Kernel\Http\Router;

// Initialize the router for API endpoints
new Router();
```

### 4. Client-side Initialization
```js
// REST API Client
const Request = new window.wplibs.Request({
    base_url: wpApiSettings.root, // WordPress REST API base
    rest_nonce: wpApiSettings.nonce
});

// Vue.js App (example)
const app = Vue.createApp({
    // Your Vue components here
});
app.mount('#wp-libs-app');
```


## Passing Data from PHP to JavaScript

You can use `wp_localize_script` to provide REST URLs, nonces, and configuration to your JavaScript/Vue code.

### Example Localization
```php
wp_localize_script('wp-libs-js', 'wpLibsConfig', [
    'api_base'   => rest_url('wp/v2'), // WordPress REST API base
    'nonce'      => wp_create_nonce('wp_rest'),
    'user_roles' => wp_get_current_user()->roles,
    'vue_data'   => [ /* Data for Vue components */ ]
]);
```

### Client-side API Initialization
```js
const Request = new window.wplibs.Request({
    base_url: wpLibsConfig.api_base,
    rest_nonce: wpLibsConfig.nonce
});
```

## Vue.js and Bootstrap Integration

wp-libs supports native Vue.js 3 components with Bootstrap 5 styling for building modern WordPress admin interfaces.

### Example Vue Component
```js
// In js/vue-components.js
const { createApp } = Vue;

const DataListComponent = {
    template: `
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="mb-3">Data List</h3>
                    <button class="btn btn-primary" @click="loadData">Load Data</button>
                    <ul class="list-group mt-3" v-if="items.length">
                        <li class="list-group-item" v-for="item in items" :key="item.id">
                            {{ item.title }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    `,
    data() {
        return {
            items: []
        };
    },
    methods: {
        async loadData() {
            try {
                const response = await Request.get('data/list');
                if (response.success) {
                    this.items = response.data;
                }
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }
    }
};

// Mount the app
const app = createApp(DataListComponent);
app.mount('#wp-libs-data-list');
```

### WordPress Integration
```php
// In your plugin/theme
function enqueue_wp_libs_scripts() {
    wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue@3', [], '3.0', true);
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css', [], '5.0');
    wp_enqueue_script('wp-libs-js', plugin_dir_url(__FILE__) . 'js/request.js', [], '1.0', true);
    wp_enqueue_script('wp-libs-vue', plugin_dir_url(__FILE__) . 'js/vue-components.js', ['vue', 'wp-libs-js'], '1.0', true);
}
add_action('admin_enqueue_scripts', 'enqueue_wp_libs_scripts');
```

## Domain-Driven Design (DDD) Backend

The PHP backend follows DDD principles with a layered architecture:

- **Domain Layer**: Business logic and entities (e.g., User, Data models).
- **Application Layer**: Services and use cases (e.g., AnalyticService).
- **Infrastructure Layer**: Controllers, repositories, and external integrations.
- **Presentation Layer**: REST API endpoints with BaseController.

### Example Service (Application Layer)
```php
// In src/Services/AnalyticService.php
class AnalyticService {
    public function getUpdateDate() {
        // Domain logic for analytics
        return date('Y-m-d H:i:s');
    }
}
```

### Controller Integration
```php
// In AppController.php
public function get_analytics(WP_REST_Request $request): WP_REST_Response {
    $analytics = new AnalyticService();
    $results = $analytics->getUpdateDate();
    
    return new WP_REST_Response([
        'success' => true,
        'data' => ['update' => $results]
    ], 200);
}
```

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