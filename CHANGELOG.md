# Changelog

## [Unreleased]

### Added
- New `css/` directory for styles.
- New initialization class `src/Bootstrap.php`.
- New HTTP request type handler `src/Kernel/Http/TypeRequest.php`.
- New directory `src/Menu/` for menu management classes.
- New request detection utility `src/RequestDetect.php`.
- Added `wplibs_register_controllers` filter in `Router.php` for dynamic controller registration.
- Added `GET` parameters serialization, `onUnauthorized` callback, and improved error handling in the JS API client (`js/request.js`).

### Changed
- **Namespace Refactoring**: Changed base namespace from `App` to `WpLibs` across all PHP files (`composer.json`, `Controllers`, `Kernel`, `Utils`, templates).
- **JS API Client Refactoring**: Renamed `Request` class to `XTRequest` and changed global namespace from `window.wplibs.Request` to `window.vcom.XTRequest` in `js/request.js`.
- `Router.php` no longer hardcodes `AppController` and strictly requires controllers to be instances of `BaseController`.
- Updated documentation (`README.md`, `SERVER_API.md`) to reflect the new `WpLibs` namespace.
- Updated template `templates/menu-nodata.php` to use the new `WpLibs\Templates` namespace.
