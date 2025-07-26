<?php

declare(strict_types=1);

namespace DrizzlePHP\Query;

use DrizzlePHP\Condition\Condition;
use DrizzlePHP\Schema\Table;
use InvalidArgumentException;
use PDO;
/**
 * Query builder for DELETE operations
 */
class DeleteQuery
{
    private Table $table;
    private ?Condition $whereCondition = null;

    public function __construct(private PDO $pdo) {}

    public function from(Table $table): self
    {
        if (!$table->isWritable()) {
            throw new InvalidArgumentException("Cannot delete from read-only view: {$table->tableName}");
        }
        $this->table = $table;
        return $this;
    }

    public function where(Condition $condition): self
    {
        $this->whereCondition = $condition;
        return $this;
    }

    public function execute(): int
    {
        if (!$this->table) {
            throw new InvalidArgumentException('Table is required');
        }

        $params = [];
        $sql = "DELETE FROM {$this->table->tableName}";
        
        if ($this->whereCondition) {
            $whereSQL = $this->whereCondition->toSQL($params);
            $sql .= " WHERE $whereSQL";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
