<?php

declare(strict_types=1);

namespace DrizzlePHP\Builders;

use DrizzlePHP\Exceptions\InvalidColumnException;
use DrizzlePHP\Exceptions\InvalidTableException;
use DrizzlePHP\Schema\Schema;
use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $columns = [];
    private array $wheres = [];
    private array $orders = [];
    private array $joins = [];
    private array $bindings = [];
    private ?int $limitCount = null;
    private ?int $offsetCount = null;
    private string $schemaClass;

    /** @var array<string, string> [table => schemaClass] */
    private array $joinSchemas = [];

    public function __construct(PDO $pdo, string $schemaClass)
    {
        $this->pdo = $pdo;
        $this->schemaClass = $schemaClass;
        $this->table = $schemaClass::getTableName();
        $this->joinSchemas[$this->table] = $schemaClass;
    }

    public function select(array $columns = ['*']): self
    {
        foreach ($columns as $column) {
            $this->validateColumn($column);
        }
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->validateColumn($column);
        $placeholder = $this->generatePlaceholder($column);
        $this->wheres[] = "{$column} {$operator} :{$placeholder}";
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->validateColumn($column);
        $placeholders = [];

        foreach ($values as $i => $value) {
            $placeholder = $this->generatePlaceholder($column . '_' . $i);
            $placeholders[] = ":{$placeholder}";
            $this->bindings[$placeholder] = $value;
        }

        $this->wheres[] = "{$column} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->validateColumn($column);
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function join(string $joinTable, string $joinSchemaClass, string $leftColumn, string $operator, string $rightColumn, string $type = 'INNER'): self
    {
        // Register the join table and validate it
        $this->joinSchemas[$joinTable] = $joinSchemaClass;

        $this->validateColumn($leftColumn);
        $this->validateColumn($rightColumn);

        $this->joins[] = strtoupper($type) . " JOIN {$joinTable} ON {$leftColumn} {$operator} {$rightColumn}";
        return $this;
    }

    public function leftJoin(string $joinTable, string $joinSchemaClass, string $leftColumn, string $operator, string $rightColumn): self
    {
        return $this->join($joinTable, $joinSchemaClass, $leftColumn, $operator, $rightColumn, 'LEFT');
    }

    public function rightJoin(string $joinTable, string $joinSchemaClass, string $leftColumn, string $operator, string $rightColumn): self
    {
        return $this->join($joinTable, $joinSchemaClass, $leftColumn, $operator, $rightColumn, 'RIGHT');
    }

    public function limit(int $count): self
    {
        $this->limitCount = $count;
        return $this;
    }

    public function offset(int $count): self
    {
        $this->offsetCount = $count;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return (int) $stmt->fetchColumn();
    }

    private function buildSelectQuery(): string
    {
        $columns = empty($this->columns) ? '*' : implode(', ', $this->columns);
        $sql = "SELECT {$columns} FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitCount !== null) {
            $sql .= " LIMIT {$this->limitCount}";
        }

        if ($this->offsetCount !== null) {
            $sql .= " OFFSET {$this->offsetCount}";
        }

        return $sql;
    }

    private function validateColumn(string $column): void
	{
		// If column contains SQL expressions (like alias, function, etc.) — skip validation
		if (
			str_contains($column, ' AS ') ||
			str_contains($column, '(') ||
			str_contains($column, ')')
		) {
			return;
		}

		// Fully-qualified e.g., paket.nama
		if (str_contains($column, '.')) {
			[$table, $col] = explode('.', $column, 2);

			if ($table === $this->schemaClass::getTableName()) {
				$columns = $this->schemaClass::getColumns();
				if (!isset($columns[$col])) {
					throw new InvalidColumnException("Column '{$column}' does not exist in schema");
				}
				return;
			}

			// Optional: allow columns from joined tables by skipping or mapping manually
			return;
		}

		// No join, allow non-prefixed
		if (empty($this->joins)) {
			$columns = $this->schemaClass::getColumns();
			if (!isset($columns[$column])) {
				throw new InvalidColumnException("Column '{$column}' does not exist in schema");
			}
			return;
		}

		throw new InvalidColumnException("Column '{$column}' must be prefixed with a table name");
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
