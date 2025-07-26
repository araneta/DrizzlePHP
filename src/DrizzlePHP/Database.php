<?php

declare(strict_types=1);

namespace DrizzlePHP;

use DrizzlePHP\Column\Column;
use DrizzlePHP\Query\DeleteQuery;
use DrizzlePHP\Query\InsertQuery;
use DrizzlePHP\Query\SelectQuery;
use DrizzlePHP\Query\UpdateQuery;
use PDO;
use PDOStatement;

/**
 * Main database connection and query builder
 */
class Database
{
    public function __construct(private PDO $pdo) {}

    /**
     * Start a SELECT query
     * 
     * @param Column ...$columns Columns to select (optional)
     * @return SelectQuery
     */
    public function select(Column ...$columns): SelectQuery
    {
        return (new SelectQuery($this->pdo))->select(...$columns);
    }

    /**
     * Start an INSERT query
     * 
     * @return InsertQuery
     */
    public function insert(): InsertQuery
    {
        return new InsertQuery($this->pdo);
    }

    /**
     * Start an UPDATE query
     * 
     * @return UpdateQuery
     */
    public function update(): UpdateQuery
    {
        return new UpdateQuery($this->pdo);
    }

    /**
     * Start a DELETE query
     * 
     * @return DeleteQuery
     */
    public function delete(): DeleteQuery
    {
        return new DeleteQuery($this->pdo);
    }

    /**
     * Get the underlying PDO connection
     * 
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Begin a database transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }

    /**
     * Execute a raw SQL query
     * 
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Check if currently in a transaction
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}