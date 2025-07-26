<?php

declare(strict_types=1);

namespace DrizzlePHP\Query;

use DrizzlePHP\Schema\{Table, View};
use DrizzlePHP\Column\Column;
use DrizzlePHP\Condition\Condition;
use PDO;
use InvalidArgumentException;

/**
 * Query builder for UPDATE operations
 */
class UpdateQuery
{
    private ?Table $table = null;
    private array $setValues = [];
    private ?Condition $whereCondition = null;

    public function __construct(private PDO $pdo) {}

    public function table(Table $table): self
    {
        if (!$table->isWritable() && !($table instanceof View && $table->isUpdatable())) {
            throw new InvalidArgumentException("Cannot update read-only view: {$table->tableName}");
        }
        $this->table = $table;
        return $this;
    }

    /**
     * Set values using column objects for type safety
     * Example: ->set([
     *     $users->name => 'Jane Doe',
     *     $users->email => 'jane@example.com'
     * ])
     */
    public function set(array|Column $data, mixed $value = null): self
    {
        if ($data instanceof Column && $value !== null) {
            // Single column-value pair: ->set($users->name, 'John')
            $this->setValues[$data->name] = $value;
        } elseif (is_array($data)) {
            // Array of column-value pairs
            foreach ($data as $key => $val) {
                if ($key instanceof Column) {
                    // Convert column object to column name
                    $this->setValues[$key->name] = $val;
                } else {
                    // Still support string keys for backward compatibility
                    $this->setValues[$key] = $val;
                }
            }
        } else {
            throw new InvalidArgumentException('Invalid set parameters');
        }
        return $this;
    }

    public function where(Condition $condition): self
    {
        $this->whereCondition = $condition;
        return $this;
    }

    public function execute(): int
    {
        if (!$this->table || empty($this->setValues)) {
            throw new InvalidArgumentException('Table and set values are required');
        }

        $params = [];
        
        // SET clause
        $setParts = [];
        foreach ($this->setValues as $column => $value) {
            $paramName = "set_$column";
            $setParts[] = "$column = :$paramName";
            $params[$paramName] = $value;
        }
        
        $sql = "UPDATE {$this->table->tableName} SET " . implode(', ', $setParts);
        
        // WHERE clause
        if ($this->whereCondition) {
            $whereSQL = $this->whereCondition->toSQL($params);
            $sql .= " WHERE $whereSQL";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get the generated SQL and parameters (for debugging)
     */
    public function toSQL(): array
    {
        if (!$this->table || empty($this->setValues)) {
            throw new InvalidArgumentException('Table and set values are required');
        }

        $params = [];
        
        // SET clause
        $setParts = [];
        foreach ($this->setValues as $column => $value) {
            $paramName = "set_$column";
            $setParts[] = "$column = :$paramName";
            $params[$paramName] = $value;
        }
        
        $sql = "UPDATE {$this->table->tableName} SET " . implode(', ', $setParts);
        
        // WHERE clause
        if ($this->whereCondition) {
            $whereSQL = $this->whereCondition->toSQL($params);
            $sql .= " WHERE $whereSQL";
        }

        return [$sql, $params];
    }
}