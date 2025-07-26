<?php

declare(strict_types=1);

namespace DrizzlePHP\Column;

/**
 * String/Text column type
 */
class StringColumn extends Column
{
    public function __construct(string $name, string $tableName, public readonly int $maxLength = 255)
    {
        parent::__construct($name, $tableName);
    }
}
