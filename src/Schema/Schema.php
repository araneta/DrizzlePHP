<?php

declare(strict_types=1);

namespace DrizzlePHP\Schema;

use DrizzlePHP\Attributes\Table;
use DrizzlePHP\Attributes\Column;
use DrizzlePHP\Exceptions\SchemaException;
use ReflectionClass;

abstract class Schema
{
    private static array $tableCache = [];
    private static array $columnCache = [];

    public static function getTableName(): string
    {
        $class = static::class;
        
        if (!isset(self::$tableCache[$class])) {
            $reflection = new ReflectionClass($class);
            $tableAttrs = $reflection->getAttributes(Table::class);
            
            if (empty($tableAttrs)) {
                throw new SchemaException("Table attribute not found on {$class}");
            }
            
            self::$tableCache[$class] = $tableAttrs[0]->newInstance()->name;
        }
        
        return self::$tableCache[$class];
    }

    public static function getColumns(): array
    {
        $class = static::class;
        
        if (!isset(self::$columnCache[$class])) {
            $reflection = new ReflectionClass($class);
            $columns = [];
            
            foreach ($reflection->getProperties() as $property) {
                $columnAttrs = $property->getAttributes(Column::class);
                if (!empty($columnAttrs)) {
                    $column = $columnAttrs[0]->newInstance();
                    $columns[$property->getName()] = $column;
                }
            }
            
            self::$columnCache[$class] = $columns;
        }
        
        return self::$columnCache[$class];
    }

    public static function getPrimaryKey(): ?string
    {
        $columns = static::getColumns();
        
        foreach ($columns as $propertyName => $column) {
            if ($column->primary) {
                return $propertyName;
            }
        }
        
        return null;
    }
}
