<?php

declare(strict_types=1);

namespace DrizzlePHP;

use DrizzlePHP\Builders\QueryBuilder;
use DrizzlePHP\Builders\InsertBuilder;
use DrizzlePHP\Builders\UpdateBuilder;
use DrizzlePHP\Builders\DeleteBuilder;
use PDO;

class DrizzlePHP
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function select(string $schemaClass): QueryBuilder
    {
        return new QueryBuilder($this->pdo, $schemaClass);
    }

    public function insert(string $schemaClass): InsertBuilder
    {
        return new InsertBuilder($this->pdo, $schemaClass);
    }

    public function update(string $schemaClass): UpdateBuilder
    {
        return new UpdateBuilder($this->pdo, $schemaClass);
    }

    public function delete(string $schemaClass): DeleteBuilder
    {
        return new DeleteBuilder($this->pdo, $schemaClass);
    }
}
