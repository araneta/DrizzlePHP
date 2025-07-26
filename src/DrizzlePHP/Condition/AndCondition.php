<?php

declare(strict_types=1);

namespace DrizzlePHP\Condition;

/**
 * AND condition
 */
class AndCondition implements Condition
{
    public function __construct(
        private Condition $left,
        private Condition $right
    ) {}

    public function toSQL(array &$params): string
    {
        return "({$this->left->toSQL($params)} AND {$this->right->toSQL($params)})";
    }
}
