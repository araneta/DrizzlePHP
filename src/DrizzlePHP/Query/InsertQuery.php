<?php

declare(strict_types=1);

namespace DrizzlePHP\Query;

use DrizzlePHP\Schema\Table;
use DrizzlePHP\Column\Column;
use PDO;
use InvalidArgumentException;

/**
 * Query builder for INSERT operations
 */
class InsertQuery
{
    private ?Table $table = null;
    private array $values = [];

    public function __construct(private PDO $pdo) {}

    public function into(Table $table): self
    {
        if (method_exists($table, 'isWritable') && !$table->isWritable()) {
            throw new InvalidArgumentException("Cannot insert into read-only view: {$table->tableName}");
        }
        $this->table = $table;
        return $this;
    }

    /**
     * Set values using column objects for type safety
     * This method properly handles Column objects as keys
     */
    public function values(array $data): self
    {
        // Clear existing values
        $this->values = [];
        
        // Process each key-value pair
        foreach ($data as $key => $value) {
            if ($key instanceof Column) {
                // Extract column name from Column object
                $columnName = $key->name;
                $this->values[$columnName] = $value;
                
                echo "DEBUG: Added column '{$columnName}' with value: " . var_export($value, true) . "\n";
            } elseif (is_string($key)) {
                // Direct string key (backward compatibility)
                $this->values[$key] = $value;
                
                echo "DEBUG: Added string key '{$key}' with value: " . var_export($value, true) . "\n";
            } else {
                throw new InvalidArgumentException(
                    "Invalid key type: " . gettype($key) . ". Expected Column object or string."
                );
            }
        }
        
        echo "DEBUG: Final values array: " . var_export($this->values, true) . "\n";
        
        return $this;
    }

    /**
     * Alternative method with explicit column-value pairs
     * This is the safest method to use
     */
    public function set(Column $column, mixed $value): self
    {
        $this->values[$column->name] = $value;
        return $this;
    }

    /**
     * Clear all values
     */
    public function clearValues(): self
    {
        $this->values = [];
        return $this;
    }

    /**
     * Get current values (for debugging)
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function execute(): bool
    {
        if (!$this->table) {
            throw new InvalidArgumentException('Table is required. Use ->into($table) first.');
        }
        
        if (empty($this->values)) {
            throw new InvalidArgumentException('Values are required. Use ->values($data) or ->set($column, $value) first.');
        }

        $columns = implode(', ', array_keys($this->values));
        $placeholders = ':' . implode(', :', array_keys($this->values));
        
        $sql = "INSERT INTO {$this->table->tableName} ($columns) VALUES ($placeholders)";
        
        echo "DEBUG: Generated SQL: $sql\n";
        echo "DEBUG: Parameters: " . var_export($this->values, true) . "\n";
        
        $stmt = $this->pdo->prepare($sql);
        
        if (!$stmt) {
            throw new InvalidArgumentException('Failed to prepare SQL statement: ' . implode(', ', $this->pdo->errorInfo()));
        }
        
        $result = $stmt->execute($this->values);
        
        if (!$result) {
            throw new InvalidArgumentException('Failed to execute statement: ' . implode(', ', $stmt->errorInfo()));
        }
        
        return $result;
    }

    /**
     * Get the generated SQL and parameters (for debugging)
     */
    public function toSQL(): array
    {
        if (!$this->table || empty($this->values)) {
            throw new InvalidArgumentException('Table and values are required');
        }

        $columns = implode(', ', array_keys($this->values));
        $placeholders = ':' . implode(', :', array_keys($this->values));
        
        $sql = "INSERT INTO {$this->table->tableName} ($columns) VALUES ($placeholders)";
        
        return [$sql, $this->values];
    }

    /**
     * Execute and get the last inserted ID
     */
    public function executeAndGetId(): int|string
    {
        $this->execute();
        return $this->pdo->lastInsertId();
    }
}