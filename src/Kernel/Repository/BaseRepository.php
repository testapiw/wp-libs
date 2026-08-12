<?php

namespace WpLibs\Kernel\Repository;

use wpdb;

abstract class BaseRepository
{
    protected wpdb $db;
    protected string $table;
    
    public function hideErrors()
    {
        $this->db->show_errors(false);
        $this->db->suppress_errors(true);
    }
   
    public function getTableName()
    {
        return $this->table;
    }

    protected function query(string $query, array $values)
    {
        $sql = $this->db->prepare($query, ...$values);
        $result = $this->db->query($sql);
        $this->log($sql);
        return $result;
    }

    protected function log(string $sql = '')
    {
        if ($this->db->last_error) {
            error_log(print_r($this->db->last_error, true));

            $sql = $this->db->last_query;
            error_log("Failed SQL: $sql");
        }
    }

    /**
     * Starts a transaction
     */
    public function transaction(): void
    {
        $this->db->query('START TRANSACTION');
    }

    /**
     * Commits the transaction (saves changes)
     */
    public function commit(): void
    {
        $this->db->query('COMMIT');
    }

    /**
     * Rolls back the transaction (undoes all changes)
     */
    public function rollback(): void
    {
        $this->db->query('ROLLBACK');
    }

    public function bulkUpdateByKey(array $changes, array $columns): bool
    {
        return $this->bulkUpdate($changes, $columns, 'key');
    }

    public function bulkUpdateById(array $changes, array $columns): bool
    {
        return $this->bulkUpdate($changes, $columns, 'id');
    }

    /**
     * Bulk update of fields by key.
     *
     * @param array $changes ['key1' => ['col1' => val1, 'col2' => val2], ...]
     * @param array $columns ['col1', 'col2']
     * @return bool
     */
    public function bulkUpdate(array $changes, array $columns, string $key = 'key'): bool
    {
        if (empty($changes)) {
            return true;
        }

        $setClause = $this->buildMultiCaseWhenSql($columns, $changes, $key);

        $ids = array_keys($changes);
        $escapedIds = array_map('esc_sql', $ids);
        $inClause = "'" . implode("','", $escapedIds) . "'";

        $sql = "UPDATE {$this->table} SET $setClause WHERE `{$key}` IN ($inClause)";
       // error_log( $sql );
        return $this->db->query($sql) !== false;
    }


    /**
     *  [
     *   'btc' => [
     *     'status' => 'excluded',
     *     'status_at' => '2025-07-17 23:59:00',
     *     --- 'changed_user_id' => 42
     *   ],
     *   ...
     * ]
     *  $columns = ['status', 'status_at', 'status_changed_by_user_id'];
     */
    private function buildMultiCaseWhenSql(array $columns, array $updates, string $key = 'key'): string
    {
        $sqlParts = [];

        foreach ($columns as $column) {
            $columnData = [];
            foreach ($updates as $k => $data) {
                if (array_key_exists($column, $data)) {
                    $columnData[$k] = $data[$column];
                }
            }

            if (!empty($columnData)) {
                $sqlParts[] = $this->buildCaseWhenSql($column, $columnData, $key);
            }
        } 

        return implode(",\n", $sqlParts);
    }


    /**
     * Builds a SQL CASE WHEN for updating columns
     * Example: `status` = CASE `key` WHEN 'btc' THEN 'active' ...
     */
    protected function buildCaseWhenSql(string $column, array $data, string $key = 'key'): string
    {
        $caseSql = "CASE `{$key}`";
        foreach ($data as $k => $value) {
            $keyEsc = esc_sql($k);

            if ($value === null) {
                $caseSql .= " WHEN '{$keyEsc}' THEN NULL";
            } elseif (is_int($value) || is_float($value)) {
                $caseSql .= " WHEN '{$keyEsc}' THEN {$value}";
            } else {
                $valEsc = esc_sql($value);
                $caseSql .= " WHEN '{$keyEsc}' THEN '{$valEsc}'";
            }
        }
        $caseSql .= " END";

        return "`$column` = $caseSql";
    }

    /**
     * Batch insert with automatic chunking
     */
    public function bulkInsert(array $rows, int $batchSize = 1000): bool
    {
        if (empty($rows)) {
            return false;
        }

        $columns = array_keys(reset($rows)); // take the keys of the first row
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '%s')) . ')';

        $batches = array_chunk($rows, $batchSize);
        foreach ($batches as $batch) {
            $sql = "INSERT INTO {$this->table} (`" . implode('`, `', $columns) . "`) VALUES ";
            $values = [];
            $prepared = [];

            foreach ($batch as $row) {
                $prepared[] = $placeholders;
                foreach ($columns as $col) {
                    $values[] = $row[$col];
                }
            }

            $query = $this->db->prepare($sql . implode(', ', $prepared), ...$values);
            if ($this->db->query($query) === false) {
                $this->log($query);
                return false;
            }
        }

        return true;
    }

    function isTableExist(?string $table = null)
    {
        if (!$table) {
            $table = $this->table;
        }
        return $this->db->get_var($this->db->prepare("SHOW TABLES LIKE %s", $table));
    }

    /**
     * Check whether the event scheduler is enabled
     */
    protected function isScheduler()
    {
       $eventScheduler = $this->db->get_var("SHOW VARIABLES LIKE 'event_scheduler'", 1);
        if (strtolower($eventScheduler) !== 'on') {
            throw new \RuntimeException("MySQL event scheduler is disabled. Enable with SET GLOBAL event_scheduler = ON;");
        }     
    }

    function renameTable(string $newName): void
    {
        $sql = "RENAME TABLE `{$this->table}` TO `{$newName}`";
        $this->db->query($sql);
        $this->log($sql);
    } 

    function createCopy(string $suffix, $isClear = false)
    {
        $newTable = $this->table . '_' . $suffix;

        $exists = $this->isTableExist($newTable);

        if ($exists) {
            if ($isClear) {
                $this->clearTable($newTable);
            } else {
                throw new \RuntimeException("Table '{$newTable}' already exists. Copy not created.");
            }
        } else {
            $createSql = "CREATE TABLE `{$newTable}` LIKE `{$this->table}`";
            $this->db->query($createSql);
            $this->log($createSql);
        }
    }

    function clearTable(?string $table = null)
    {
        if (!$table) {
            $table = $this->table;
        }
        $truncateSql = "TRUNCATE TABLE `{$table}`";
        $this->db->query($truncateSql);
        $this->log($truncateSql);
    }

    function count()
    {
        $sql = "SELECT count(*) FROM `{$this->table}`";
        $result = $this->db->get_var($sql);
        $this->log($sql);
        return $result;
    }

}
