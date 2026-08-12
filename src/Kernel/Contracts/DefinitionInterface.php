<?php

namespace WpLibs\Kernel\Contracts;

/**
 * Describes a table schema used by the query builder (AbstractQuery).
 *
 * A concrete definition maps logical field names to physical columns and
 * declares which fields are sortable, plus the default sort column.
 */
interface DefinitionInterface
{
	/**
	 * Field map: logical name => ['type' => ..., 'column' => ..., ...].
	 *
	 * @return array<string, array>
	 */
	public static function fields(): array;

	/**
	 * Sortable field map (subset of fields()).
	 *
	 * @return array<string, array>
	 */
	public static function sortable(): array;

	/**
	 * Default sort column (logical field name), or null.
	 */
	public static function defaultSortable(): ?string;
}