# Changelog

## [1.1.0] — 2026-08-12

### Added
- New `QueryValidator` (`src/Kernel/Queries/QueryValidator.php`) — validates and normalizes list query params (page, per_page, sort_column, sort_order, filters) against a `DefinitionInterface` schema. Derives filterable fields from the definition, sanitizes values by field type + `max_length`, and strips HTML/control characters from string filters.
- New `DefinitionInterface` (`src/Kernel/Contracts/DefinitionInterface.php`) — declares `fields()`, `sortable()`, `defaultSortable()` for table schema definitions used by the query builder.

### Changed
- **Query builder refactor**: `AbstractQuery` now extends `BaseRepository` (inherits `wpdb`, `query()`, `log()`, transactions, bulk helpers) and uses `QueryTrait` only to assemble SQL. Added execution helpers `runSelect()`, `runCount()`, `runUpdate()`, `runInsert()`.
- **QueryTrait cleanup**: removed dead code (`$limit`, `$query`, `$alias` property, `toSnakeCase()`), removed `$table` redeclaration (inherited from `BaseRepository`), renamed `$isPagination` → `$paginationEnabled`, added `tableRef()` helper.
- **Bug fixes**: `setOrderBy()` and `setPagination()` now accept `null` (default `QueryObject` state); `setOrderBy()` resolves the physical column via `$fields[$sortBy]['column']` instead of using the field array directly.
- `BaseRepository` namespace corrected to `WpLibs\Kernel\Repository` (was `XTBase\Kernel\Repository`).
- `QueryValidator::validateList()` now reads filters from both the nested `filters` array and top-level keys, so both request shapes work.

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
