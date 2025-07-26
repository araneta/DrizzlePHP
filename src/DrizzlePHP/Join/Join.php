<?php

declare(strict_types=1);

namespace DrizzlePHP\Join;

/**
 * Join configuration
 */
class Join
{
    public function __construct(
        public readonly Table $table,
        public readonly Condition $condition,
        public readonly string $type = 'INNER'
    ) {}
}
