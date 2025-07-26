<?php

declare(strict_types=1);

namespace DrizzlePHP\Column;

/**
 * Base Column class for type safety
 */
abstract class Column
{
    public function __construct(
        public readonly string $name,
        public readonly string $tableName
    ) {}

    public function getFullName(): string
    {
        return "{$this->tableName}.{$this->name}";
    }

    /**
     * Allow Column objects to be used as string keys
     * This prevents the "Illegal offset type" error
     */
    public function __toString(): string
    {
        return $this->name;
    }
}