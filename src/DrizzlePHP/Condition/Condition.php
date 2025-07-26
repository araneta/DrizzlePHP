<?php

declare(strict_types=1);

namespace DrizzlePHP\Condition;

/**
 * Condition interface for WHERE clauses
 */
interface Condition
{
    public function toSQL(array &$params): string;
}
