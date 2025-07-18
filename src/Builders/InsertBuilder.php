<?php

declare(strict_types=1);

namespace DrizzlePHP\Builders;

use DrizzlePHP\Exceptions\InvalidColumnException;
use DrizzlePHP\Schema\Schema;
use PDO;

// Insert Builder
class InsertBuilder
{
    private PDO $pdo;
    private string $table;
    private array $data = [];
    private string $schemaClass;

    public function __construct(PDO $pdo, string $schemaClass)
    {
        $this->pdo = $pdo;
        $this->schemaClass = $schemaClass;
        $this->table = $schemaClass::getTableName();
    }

    public function values(array $data): self
    {
        foreach ($data as $column => $value) {
            $this->validateColumn($column);
        }
        
        $this->data = $data;
        return $this;
    }

    public function execute(): bool
    {
        if (empty($this->data)) {
            throw new Exception("No data provided for insert");
        }
        
        $columns = array_keys($this->data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->data);
    }

    public function returning(string $column = 'id'): mixed
    {
        $this->execute();
        
        // For databases that support RETURNING clause
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            return $this->pdo->lastInsertId();
        }
        
        return $this->pdo->lastInsertId();
    }

    private function validateColumn(string $column): void
    {
        $columns = $this->schemaClass::getColumns();
        
        if (!isset($columns[$column])) {
            throw new InvalidArgumentException("Column '{$column}' does not exist in schema");
        }
    }
}
