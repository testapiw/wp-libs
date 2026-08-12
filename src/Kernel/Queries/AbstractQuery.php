<?php

namespace WpLibs\Kernel\Queries;

use wpdb;
use WpLibs\Kernel\Queries\QueryObject;
use WpLibs\Kernel\Repository\BaseRepository;
use WpLibs\Kernel\Queries\Traits\QueryTrait;

/**
 * Base query builder.
 *
 * Extends BaseRepository so the execution + error-logging logic (wpdb, query(),
 * log(), transactions, bulk helpers) is inherited, and uses QueryTrait to
 * assemble SQL. Concrete query classes only need to provide a Definition and
 * call the run*() helpers.
 */
abstract class AbstractQuery extends BaseRepository
{
	use QueryTrait;

	protected array $compares = [ '=', '!=', '>', '<', '>=', '<=', 'like', 'in', 'is null', 'is not null', 'between' ];

	protected array $exclud = [];

	/**
	 * @param wpdb|null $db Optional wpdb instance; falls back to global $wpdb.
	 */
	public function __construct( ?wpdb $db = null )
	{
		global $wpdb;
		$this->db = $db ?? $wpdb;
	}

	public function setParams( QueryObject $queryObject ): static
	{
		$definition = $queryObject->getDefinition();
		$sqlWhere   = $this->buildSqlFromConditions( $queryObject->getFilters(), $definition->fields() );

		$this->where( $sqlWhere );

		$this->setOrderBy( $queryObject->getOrder(), $definition->sortable(), $definition->defaultSortable() );
		$this->setPagination( $queryObject->getPagination() );

		return $this;
	}

	public function setUpdateFields( QueryObject $queryObject ): static
	{
		$definition = $queryObject->getDefinition();
		$fields     = $definition->fields();
		$updateData = $queryObject->getUpdateFields();

		$updateParts = [];

		foreach ( $updateData as $entry ) {
			$pairs = [];

			foreach ( $entry as $column => $value ) {
				if ( ! is_null( $value ) ) {
					$format  = is_numeric( $value ) ? '%d' : '%s';
					$pairs[] = $this->db->prepare( "`$column` = $format", $value );
				} else {
					$pairs[] = "`$column` = NULL";
				}
			}

			if ( ! empty( $pairs ) ) {
				$updateParts[] = implode( ', ', $pairs );
			}
		}

		$this->setUpdate( $updateParts );

		return $this;
	}

	public function prepareCreate( QueryObject $queryObject ): static
	{
		$definition = $queryObject->getDefinition();
		$fields     = $definition->fields();
		$data       = $queryObject->getUpdateFields();

		if ( empty( $data ) ) {
			throw new \InvalidArgumentException( 'No data provided for insert.' );
		}

		$columns         = array_keys( $data[0] );
		$escapedColumns  = array_map( fn( $col ) => "`$col`", $columns );

		$valuesList = [];

		foreach ( $data as $entry ) {
			$placeholders = [];
			$values       = [];

			foreach ( $columns as $col ) {
				$value = $entry[ $col ] ?? null;

				if ( is_null( $value ) ) {
					$placeholders[] = '%s';
					$values[]       = null;
				} else {
					$placeholders[] = is_numeric( $value ) ? '%d' : '%s';
					$values[]       = $value;
				}
			}

			$prepared = $this->db->prepare( '(' . implode( ', ', $placeholders ) . ')', ...$values );
			$prepared = str_replace( "'NULL'", 'NULL', $prepared );
			$valuesList[] = $prepared;
		}

		$columns = implode( ', ', $escapedColumns );
		$values  = implode( ",\n", $valuesList );

		$this->setInsert( [
			'columns' => $columns,
			'values'  => $values,
		] );

		return $this;
	}

	// only update
	public function setUpdateParams( QueryObject $queryObject ): static
	{
		$definition = $queryObject->getDefinition();
		$fields     = $definition->fields();
		$filters    = $queryObject->getFilters();

		if ( ! isset( $filters['id'] ) || ! is_numeric( $filters['id'] ) || (int) $filters['id'] <= 0 ) {
			throw new \Exception( 'Unknown or invalid ID' );
		}

		$id = (int) $filters['id'];
		$this->where( '`id` = ' . $id );

		$parts = [];
		foreach ( $filters as $field => $value ) {
			if ( $field === 'id' ) {
				continue;
			}
			if ( ! isset( $fields[ $field ] ) ) {
				throw new \Exception( "Unknown field '$field'" );
			}

			$item = $fields[ $field ];
			if ( ! isset( $item['column'] ) ) {
				throw new \Exception( "Field definition for '$field' must include a 'field' key." );
			}

			$column = $item['column'];

			if ( ! is_null( $value ) ) {
				$format  = is_numeric( $value ) ? '%d' : '%s';
				$parts[] = $this->db->prepare( "`$column` = $format", $value );
			} else {
				$parts[] = "`$column` = NULL";
			}
		}

		$this->setUpdate( $parts );

		return $this;
	}

	public function buildSqlFromConditions( array $filter, array $fields ): string
	{
		return $this->processFilterGroup( $filter, $fields );
	}

	function processFilterGroup( array $group, array $fields ): string
	{
		$join = strtoupper( $group['join'] ?? 'AND' );
		$parts = [];

		if ( empty( $group['conditions'] ) ) {
			return ' 1=1 ';
		}

		foreach ( $group['conditions'] as $cond ) {
			// Nested group
			if ( isset( $cond['conditions'] ) && is_array( $cond['conditions'] ) ) {
				$parts[] = '(' . $this->processFilterGroup( $cond, $fields ) . ')';
				continue;
			}

			// Single filter
			if ( isset( $cond['field'], $cond['compare'], $cond['value'] ) ) {
				$field   = $cond['field'];
				$compare = strtolower( $cond['compare'] );
				$value   = $cond['value'];

				if ( ! isset( $fields[ $field ] ) ) {
					throw new \Exception( "Unknown field '$field'" );
				}

				$column = $fields[ $field ]['column'];
				$type   = $fields[ $field ]['type'];

				if ( ! in_array( $compare, $this->compares ) ) {
					throw new \Exception( 'Invalid filter structure' );
				}

				if ( in_array( $compare, [ 'is null', 'is not null' ] ) ) {
					$parts[] = "`$column` $compare";
					continue;
				}
               
                if ( $compare === 'like' ) {
                    $like = '%' . $this->db->esc_like( $value ) . '%';

					preg_match_all( '/%[sdf]/', $column, $matches );
					$count = count( $matches[0] );

					if ( $count > 1 ) {
						$args = array_fill( 0, $count, $like );
						$sql  = $this->db->prepare( $column, ...$args );
					} else {
						$sql = $this->db->prepare( "`$column` LIKE %s", $like );
					}

					if ( ! empty( $sql ) ) {
						$parts[] = $sql;
					} else {
						throw new \Exception( 'Invalid filter structure' );
					}
					continue;
				}

                if ( $compare === 'between' && is_array( $value ) ) {
                    $sql = false;
                    if ( ! empty( $value['from'] ) && ! empty( $value['to'] ) ) {
                        $sql = $this->db->prepare( "`$column` BETWEEN %s AND %s", $value['from'], $value['to'] );
                    } elseif ( ! empty( $value['from'] ) ) {
                        $sql = $this->db->prepare( "`$column` >= %s", $value['from'] );
                    } elseif ( ! empty( $value['to'] ) ) {
                        $sql = $this->db->prepare( "`$column` <= %s", $value['to'] );
                    } else {
                        throw new \Exception( 'Invalid filter structure: missing "from" or "to" value.' );
                    }

                    if ( ! empty( $sql ) ) {
                        $parts[] = $sql;
                    }

                    continue;
                }

                if ( $compare === 'in' && is_array( $value ) ) {
                    $placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
                    $sql = $this->db->prepare( "`$column` IN ($placeholders)", ...$value );
                    if ( ! empty( $sql ) ) {
                        $parts[] = $sql;
                    } else {
                        throw new \Exception( 'Invalid filter structure' );
                    }
                    continue;
                }

                $placeholder = ( in_array( $type, [ 'int', 'bool' ] ) && is_numeric( $value ) ) ? '%d' : '%s';
                $sql = $this->db->prepare( "`$column` {$compare} {$placeholder}", $value );
                if ( ! empty( $sql ) ) {
                    $parts[] = $sql;
                } else {
                    throw new \Exception( 'Invalid filter structure' );
                }
                continue;
            }

            throw new \Exception( 'Invalid filter structure' );
        }

        if ( ! empty( $parts ) ) {
            return implode( " $join ", $parts );
        }
        return '';
    }

    public function setOrderBy( ?array $params, array $fields, ?string $default = null ): static
    {
        $sortBy = $params['column'] ?? $default;

        if ( ! $sortBy || ! isset( $fields[ $sortBy ] ) ) {
            return $this;
        }

        $order = strtoupper( $params['order'] ?? 'ASC' );
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'ASC';
        }

        $column = $fields[ $sortBy ]['column'] ?? $sortBy;
        $this->setOrderByClause( $column . ' ' . $order );
        return $this;
    }

	// ---------------------------------------------------------------------
	// Execution helpers — run the built SQL through the inherited wpdb and
	// log any errors via BaseRepository::log().
	// ---------------------------------------------------------------------

	/**
	 * Execute the built SELECT and return rows as associative arrays.
	 */
	public function runSelect(): array
	{
		$sql  = $this->select();
		$rows = $this->db->get_results( $sql, ARRAY_A );
		$this->log( $sql );
		return $rows ?: [];
	}

	/**
	 * Execute the built COUNT query and return the total.
	 */
	public function runCount(): int
	{
		$sql   = $this->total();
		$count = (int) $this->db->get_var( $sql );
		$this->log( $sql );
		return $count;
	}

	/**
	 * Execute the built UPDATE and return whether it succeeded.
	 */
	public function runUpdate(): bool
	{
		$sql = $this->update();
		if ( '' === $sql ) {
			return false;
		}
		$result = $this->db->query( $sql );
		$this->log( $sql );
		return false !== $result;
	}

	/**
	 * Execute the built INSERT and return whether it succeeded.
	 */
	public function runInsert(): bool
	{
		$sql = $this->insert();
		if ( '' === $sql ) {
			return false;
		}
		$result = $this->db->query( $sql );
		$this->log( $sql );
		return false !== $result;
	}
}
