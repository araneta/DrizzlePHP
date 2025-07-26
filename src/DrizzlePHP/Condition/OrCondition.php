<?php

declare(strict_types=1);

namespace DrizzlePHP\Condition;

/**
 * OR condition
 */
class OrCondition implements Condition
{
    public function __construct(
        private Condition $left,
        private Condition $right
    ) {}

    public function toSQL(array &$params): string
    {
        return "({$this->left->toSQL($params)} OR {$this->right->toSQL($params)})";
    }
}
