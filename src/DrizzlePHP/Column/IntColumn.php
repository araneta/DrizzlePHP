<?php

declare(strict_types=1);

namespace DrizzlePHP\Column;

/**
 * Integer column type
 */
class IntColumn extends Column
{
    public function __construct(string $name, string $tableName, public readonly bool $autoIncrement = false)
    {
        parent::__construct($name, $tableName);
    }
}
