<?php

declare(strict_types=1);

namespace DrizzlePHP\Builders;

use DrizzlePHP\Exceptions\InvalidColumnException;
use DrizzlePHP\Schema\Schema;
use PDO;

// Delete Builder
class DeleteBuilder
{
    private PDO $pdo;
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private string $schemaClass;

    public function __construct(PDO $pdo, string $schemaClass)
    {
        $this->pdo = $pdo;
        $this->schemaClass = $schemaClass;
        $this->table = $schemaClass::getTableName();
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->validateColumn($column);
        $placeholder = $this->generatePlaceholder($column);
        $this->wheres[] = "{$column} {$operator} :{$placeholder}";
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function execute(): bool
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    private function validateColumn(string $column): void
    {
        $columns = $this->schemaClass::getColumns();
        
        if (!isset($columns[$column])) {
            throw new InvalidArgumentException("Column '{$column}' does not exist in schema");
        }
    }

    private function generatePlaceholder(string $column): string
    {
        $base = str_replace('.', '_', $column);
        $counter = 1;
        $placeholder = $base;
        
        while (isset($this->bindings[$placeholder])) {
            $placeholder = $base . '_' . $counter++;
        }
        
        return $placeholder;
    }
}

