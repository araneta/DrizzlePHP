<?php

declare(strict_types=1);

namespace DrizzlePHP\Schema;
/**
 * Base Table class
 */
abstract class Table
{
    public readonly string $tableName;
    
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }
    
    abstract public function getColumns(): array;
    
    /**
     * Check if this table supports write operations
     */
    public function isWritable(): bool
    {
        return true;
    }
}
