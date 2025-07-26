<?php

declare(strict_types=1);

namespace DrizzlePHP\Schema;
/**
 * Base View class - read-only database views
 */
abstract class View extends Table
{
    public function __construct(string $viewName)
    {
        parent::__construct($viewName);
    }
    
    /**
     * Views are read-only by default
     */
    public function isWritable(): bool
    {
        return false;
    }
    
    /**
     * Some views can be updatable - override this method for updatable views
     */
    public function isUpdatable(): bool
    {
        return false;
    }
}
