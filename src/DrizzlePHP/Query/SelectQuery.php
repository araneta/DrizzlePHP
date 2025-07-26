<?php

declare(strict_types=1);

namespace DrizzlePHP\Query;

use DrizzlePHP\Condition\Condition;
use DrizzlePHP\Column\Column;
use DrizzlePHP\Schema\Table;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use UI\Draw\Line\Join;
/**
 * Query builder for SELECT operations
 */
class SelectQuery
{
    private array $selectedColumns = [];
    private ?Table $fromTable = null;
    private array $joins = [];
    private ?Condition $whereCondition = null;
    private array $orderByColumns = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    public function __construct(private PDO $pdo) {}

    public function select(Column ...$columns): self
    {
        $this->selectedColumns = $columns;
        return $this;
    }

    public function from(Table $table): self
    {
        $this->fromTable = $table;
        return $this;
    }

    public function leftJoin(Table $table, Condition $condition): self
    {
        $this->joins[] = new Join($table, $condition, 'LEFT');
        return $this;
    }

    public function innerJoin(Table $table, Condition $condition): self
    {
        $this->joins[] = new Join($table, $condition, 'INNER');
        return $this;
    }

    public function where(Condition $condition): self
    {
        $this->whereCondition = $condition;
        return $this;
    }

    public function orderBy(Column $column, string $direction = 'ASC'): self
    {
        $this->orderByColumns[] = ['column' => $column, 'direction' => $direction];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public function toSQL(): array
    {
        if (!$this->fromTable) {
            throw new InvalidArgumentException('FROM table is required');
        }

        $params = [];
        
        // SELECT clause
        $selectColumns = empty($this->selectedColumns) 
            ? '*' 
            : implode(', ', array_map(fn(Column $col) => $col->getFullName(), $this->selectedColumns));
        
        $sql = "SELECT $selectColumns";
        
        // FROM clause
        $sql .= " FROM {$this->fromTable->tableName}";
        
        // JOIN clauses
        foreach ($this->joins as $join) {
            $joinConditionSQL = $join->condition->toSQL($params);
            $sql .= " {$join->type} JOIN {$join->table->tableName} ON $joinConditionSQL";
        }
        
        // WHERE clause
        if ($this->whereCondition) {
            $whereSQL = $this->whereCondition->toSQL($params);
            $sql .= " WHERE $whereSQL";
        }
        
        // ORDER BY clause
        if (!empty($this->orderByColumns)) {
            $orderByParts = array_map(
                fn(array $orderBy) => "{$orderBy['column']->getFullName()} {$orderBy['direction']}",
                $this->orderByColumns
            );
            $sql .= " ORDER BY " . implode(', ', $orderByParts);
        }
        
        // LIMIT clause
        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }
        
        // OFFSET clause
        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return [$sql, $params];
    }

    public function execute(): PDOStatement
    {
        [$sql, $params] = $this->toSQL();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(): array
    {
        return $this->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(): ?array
    {
        $stmt = $this->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Fetch results as objects (slower but more convenient)
     * Usage: ->fetchAllAsObjects() or ->fetchAllAsObjects(UserRecord::class)
     */
    public function fetchAllAsObjects(?string $className = null): array
    {
        $stmt = $this->execute();
        if ($className) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, $className);
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function fetchOneAsObject(?string $className = null): ?object
    {
        $stmt = $this->execute();
        if ($className) {
            $stmt->setFetchMode(PDO::FETCH_CLASS, $className);
            $result = $stmt->fetch();
        } else {
            $result = $stmt->fetch(PDO::FETCH_OBJ);
        }
        return $result ?: null;
    }
}
