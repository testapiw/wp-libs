<?php

namespace WpLibs\Kernel\Queries\Traits;

/**
 * SQL query builder trait.
 *
 * Builds SELECT / UPDATE / INSERT SQL strings from a fluent state. The host
 * class (AbstractQuery extends BaseRepository) owns the wpdb instance and the
 * error-logging helpers, so this trait only assembles SQL — it never executes.
 *
 * The raw table name lives in BaseRepository::$table; setTable() writes it and
 * tableRef() renders the backticked (optionally aliased) reference.
 *
 * @property string $table Raw table name (inherited from BaseRepository).
 */
trait QueryTrait
{
	/** @var string */
	private string $fields = '*';

	/** @var string[] */
	private array $whereParts = [];

	/** @var string[] */
	private array $updateParts = [];

	/** @var array{columns:string,values:string}[] */
	private array $insertParts = [];

	/** @var string */
	private string $orderBy = '';

	/** @var string */
	private string $join = '';

	/** @var string */
	private string $alias = '';

	/** @var string[] */
	private array $aliasSearch = [];

	/** @var string[] */
	private array $aliasReplace = [];

	/** @var int */
	private int $page = 1;

	/** @var int */
	private int $perPage = 20;

	/** @var bool */
	private bool $paginationEnabled = true;

	/** @var string */
	private string $lastSql = '';

	/**
	 * Set the target table (raw name) and optional alias.
	 */
	public function setTable( string $table, string $alias = '' ): static
	{
		$this->table = $table;
		$this->alias = $alias;
		return $this;
	}

	/**
	 * Backticked table reference, e.g. `wp_deals` or `wp_deals` AS `d`.
	 */
	private function tableRef(): string
	{
		return $this->alias
			? "`{$this->table}` AS `{$this->alias}`"
			: "`{$this->table}`";
	}

	public function where( string $sql ): static
	{
		$this->whereParts = [ $sql ];
		return $this;
	}

	public function andWhere( array $sqlParts ): static
	{
		foreach ( $sqlParts as $part ) {
			$this->whereParts[] = $part;
		}
		return $this;
	}

	public function orWhere( array $sqlParts ): static
	{
		$this->whereParts[] = '(' . implode( ' OR ', $sqlParts ) . ')';
		return $this;
	}

	public function setOrderByClause( string $orderBy ): static
	{
		$this->orderBy = $orderBy;
		return $this;
	}

	public function setFields( string $fields = '*' ): static
	{
		$this->fields = $fields;
		return $this;
	}

	public function select(): string
	{
		return "SELECT {$this->fields} FROM {$this->tableRef()} {$this->join} {$this->get()}";
	}

	public function setLeftJoin( array $data ): static
	{
		$table = $data['table'] ?? '';
		$alias = $data['alias'] ?? '';
		$on    = $data['on'] ?? '';

		if ( ! $table || ! $on ) {
			throw new \InvalidArgumentException( 'Invalid JOIN parameters' );
		}

		$this->join .= " LEFT JOIN `{$table}` {$alias} ON {$on}";
		return $this;
	}

	public function total(): string
	{
		return "SELECT COUNT(*) FROM {$this->tableRef()} {$this->join} {$this->prepareWhere()}";
	}

	public function update(): string
	{
		if ( empty( $this->whereParts ) ) {
			throw new \LogicException( 'UPDATE is not allowed!' );
		}

		if ( empty( $this->updateParts ) ) {
			return '';
		}

		$set   = implode( ', ', $this->updateParts );
		$where = $this->prepareWhere();
		return "UPDATE {$this->tableRef()} SET {$set} {$where}";
	}

	public function insert(): string
	{
		$queries = [];

		foreach ( $this->insertParts as $item ) {
			$queries[] = "INSERT INTO {$this->tableRef()} {$item['columns']} VALUES ({$item['values']})";
		}

		return implode( ";\n", $queries );
	}

	public function get(): string
	{
		$query  = $this->prepareWhere();
		$query .= $this->prepareOrder();

		if ( $this->perPage > 0 && $this->paginationEnabled ) {
			$query .= $this->buildLimitClause();
		}

		$this->lastSql = $query;
		return $query;
	}

	private function prepareWhere(): string
	{
		if ( empty( $this->whereParts ) ) {
			return '';
		}

		$where = implode( ' AND ', $this->whereParts );
		return ' WHERE ' . $this->applyAlias( $where );
	}

	private function prepareOrder(): string
	{
		if ( empty( $this->orderBy ) ) {
			return '';
		}

		return ' ORDER BY ' . $this->applyAlias( $this->orderBy );
	}

	private function applyAlias( string $sql ): string
	{
		if ( ! empty( $this->aliasSearch ) ) {
			$sql = str_replace( $this->aliasSearch, $this->aliasReplace, $sql );
		}
		return $sql;
	}

	/**
	 * Map bare field names to aliased columns in WHERE / ORDER BY.
	 *
	 * @param array $mapAlias ['id' => 'd.id', 'user_id' => 'u.ID']
	 */
	public function setAlias( array $mapAlias ): static
	{
		$this->aliasSearch  = [];
		$this->aliasReplace = [];

		foreach ( $mapAlias as $field => $replace ) {
			$this->aliasSearch[]  = '`' . $field . '`';
			[ $aliasTable, $fieldName ] = explode( '.', $replace );
			$this->aliasReplace[] = $aliasTable . '.`' . $fieldName . '`';
		}

		return $this;
	}

	public function setUpdate( array $updateParts ): static
	{
		$this->updateParts = $updateParts;
		return $this;
	}

	public function setInsert( array $insertParts ): static
	{
		$this->insertParts = $insertParts;
		return $this;
	}

	public function setPerpage( int $perPage = 20 ): static
	{
		$this->perPage = $perPage;
		return $this;
	}

	public function getUpdateParts(): array
	{
		return $this->updateParts;
	}

	public function reset(): static
	{
		$this->whereParts        = [];
		$this->orderBy           = '';
		$this->page              = 1;
		$this->fields            = '*';
		$this->updateParts       = [];
		$this->insertParts       = [];
		$this->perPage           = 20;
		$this->join              = '';
		$this->lastSql           = '';
		$this->alias             = '';
		$this->aliasSearch       = [];
		$this->aliasReplace      = [];
		$this->paginationEnabled = true;

		return $this;
	}

	/**
	 * Set page + per-page and enable pagination.
	 */
	public function setPagination( ?array $pagination ): static
	{
		$this->page              = max( 1, $pagination['page'] ?? 1 );
		$this->perPage           = max( 1, $pagination['perpage'] ?? 20 );
		$this->paginationEnabled = true;
		return $this;
	}

	public function isPagination( bool $enabled = true ): static
	{
		$this->paginationEnabled = $enabled;
		return $this;
	}

	private function buildLimitClause(): string
	{
		$offset = ( $this->page - 1 ) * $this->perPage;
		return " LIMIT {$this->perPage} OFFSET {$offset}";
	}

	public function getLastSql(): string
	{
		return $this->lastSql;
	}
}