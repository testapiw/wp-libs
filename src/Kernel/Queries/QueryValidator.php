<?php

namespace WpLibs\Kernel\Queries;

use WpLibs\Kernel\Contracts\DefinitionInterface;

/**
 * Validates and normalizes list query params against a Definition schema.
 *
 * Field types + max lengths are taken from the DefinitionInterface, so the
 * validator stays in sync with the table structure automatically.
 *
 * Returns a clean, typed array that a query builder can consume safely.
 */
class QueryValidator
{
	/** @var DefinitionInterface */
	private DefinitionInterface $definition;

	/** @var string[] Fields allowed as filters (string-ish, searchable). */
	private array $filterable;

	/**
	 * @param DefinitionInterface $definition Schema definition.
	 */
	public function __construct( DefinitionInterface $definition )
	{
		$this->definition = $definition;

		// Only string-ish fields are filterable via LIKE.
		$this->filterable = [];
		foreach ( $this->definition->fields() as $field => $meta ) {
			$type = $meta['type'] ?? 'string';
			if ( in_array( $type, [ 'string', 'text', 'varchar' ], true ) ) {
				$this->filterable[] = $field;
			}
		}
	}

	/**
	 * Validate + normalize list params.
	 *
	 * @param array $params Raw request params.
	 * @return array {
	 *     @type int    $page        Current page (1-based).
	 *     @type int    $per_page    Rows per page.
	 *     @type string $sort_column Sortable column.
	 *     @type string $sort_order  'ASC' or 'DESC'.
	 *     @type array  $filters     Column => value (LIKE match).
	 * }
	 */
	public function validateList( array $params ): array
	{
		$page     = max( 1, (int) ( $params['page'] ?? 1 ) );
		$per_page = max( 1, min( 1000, (int) ( $params['per_page'] ?? 20 ) ) );

		$sort_column = $this->sanitizeSortColumn( $params['sort_column'] ?? 'created_at' );
		$sort_order  = $this->sanitizeSortOrder( $params['sort_order'] ?? 'DESC' );

		// Filters may arrive either as top-level keys (status=paid) or nested
		// under a `filters` array (filters[status]=paid). Merge both.
		$rawFilters = $params['filters'] ?? [];
		if ( ! is_array( $rawFilters ) ) {
			$rawFilters = [];
		}

		$filters = [];
		foreach ( $this->filterable as $field ) {
			$value = $rawFilters[ $field ] ?? $params[ $field ] ?? '';
			$value = trim( (string) $value );
			if ( '' === $value ) {
				continue;
			}

			$value = $this->sanitizeFilterValue( $field, $value );
			if ( '' !== $value ) {
				$filters[ $field ] = $value;
			}
		}

		return [
			'page'        => $page,
			'per_page'    => $per_page,
			'sort_column' => $sort_column,
			'sort_order'  => $sort_order,
			'filters'     => $filters,
		];
	}

	/**
	 * Ensure the sort column is whitelisted (from the definition's sortable()).
	 */
	protected function sanitizeSortColumn( string $column ): string
	{
		$sortable = array_keys( $this->definition->sortable() );
		return in_array( $column, $sortable, true ) ? $column : ( $this->definition->defaultSortable() ?? 'id' );
	}

	/**
	 * Ensure the sort order is ASC or DESC.
	 */
	protected function sanitizeSortOrder( string $order ): string
	{
		return in_array( strtoupper( $order ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $order ) : 'DESC';
	}

	/**
	 * Sanitize a filter value according to its field type + max length.
	 */
	protected function sanitizeFilterValue( string $field, string $value ): string
	{
		$meta = $this->definition->fields()[ $field ] ?? [];
		$type = $meta['type'] ?? 'string';

		// Numeric fields: keep only numeric characters.
		if ( in_array( $type, [ 'int', 'float', 'decimal', 'double' ], true ) ) {
			$value = preg_replace( '/[^0-9.\-]/', '', $value );
		} else {
			// String fields: strip tags, control chars, collapse whitespace.
			$value = $this->sanitizeString( $value );
		}

		// Enforce max length for string fields.
		if ( ! empty( $meta['max_length'] ) ) {
			$value = mb_substr( $value, 0, (int) $meta['max_length'] );
		}

		return trim( $value );
	}

	/**
	 * Sanitize a free-text string for safe use in a LIKE filter.
	 *
	 * Strips HTML/script tags, removes control characters, and collapses
	 * runs of whitespace into a single space.
	 */
	protected function sanitizeString( string $value ): string
	{
		// Remove HTML tags + script/style blocks.
		$value = wp_strip_all_tags( $value );

		// Remove control characters (except tab/newline which we normalize below).
		$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value );

		// Collapse whitespace runs into a single space.
		$value = preg_replace( '/\s+/', ' ', $value );

		return trim( $value );
	}
}
