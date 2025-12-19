# Request – Lightweight REST Client for WordPress

`Request` is a small JavaScript wrapper around the Fetch API, built specifically for working with the **WordPress REST API**.  
It simplifies making **GET / POST / PUT / DELETE** requests, handles nonces automatically, and provides a consistent response format.

---

## Features

- Designed for WordPress REST API
- Automatic `X-WP-Nonce` support
- Unified error handling
- Simple API: `get`, `post`, `put`, `delete`
- JSON request & response handling
- Zero dependencies

---

## Installation

### Enqueue the script in WordPress

```php
wp_enqueue_script('request-js', LIBS_URL . '/js/request.js', [], LIBS_VER, true);
```

```js
window.wplibs.Request
```


## Passing Data from PHP to JavaScript

You can use `wp_localize_script` to provide REST URLs and nonces to your JavaScript code.

### Single endpoint

```php
wp_localize_script('request-js', 'localizeMenu', [
    'base_url'   => rest_url('auth/v1'),
    'nonce'      => wp_create_nonce('auth_'),
    'rest_nonce' => wp_create_nonce('wp_rest')
]);
```

### Client-side API Initialization

```js
const Request = new window.wplibs.Request({
    base_url: localizeMenu.base_url,
    nonce: localizeMenu.nonce,
    rest_nonce: localizeMenu.rest_nonce
});
```

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

- All requests are sent as JSON
- X-WP-Nonce is attached automatically
- Errors are normalized inside the client
- Easily extensible (interceptors, retries, query params)


## License

MIT