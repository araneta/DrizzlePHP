<?php

declare(strict_types=1);

namespace DrizzlePHP\Condition;

use DrizzlePHP\Column\Column;

/**
 * Not equal condition
 */
class NeCondition implements Condition
{
    public function __construct(
        private Column $column,
        private mixed $value
    ) {}

    public function toSQL(array &$params): string
    {
        $paramName = 'param_' . count($params);
        $params[$paramName] = $this->value;
        return "{$this->column->getFullName()} != :$paramName";
    }
}
