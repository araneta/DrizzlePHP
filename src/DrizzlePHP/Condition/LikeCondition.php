<?php

declare(strict_types=1);

namespace DrizzlePHP\Condition;

use DrizzlePHP\Column\Column;

/**
 * LIKE condition
 */
class LikeCondition implements Condition
{
    public function __construct(
        private Column $column,
        private string $pattern
    ) {}

    public function toSQL(array &$params): string
    {
        $paramName = 'param_' . count($params);
        $params[$paramName] = $this->pattern;
        return "{$this->column->getFullName()} LIKE :$paramName";
    }
}
