<?php
declare(strict_types=1);
namespace DrizzlePHP;

use DrizzlePHP\Column\Column;
use DrizzlePHP\Condition\{
    EqCondition,
    NeCondition,
    GtCondition,
    LtCondition,
    LikeCondition,
    AndCondition,
    OrCondition,
    Condition
};

/**
 * Helper functions for creating conditions (similar to Drizzle)
 * These functions provide a clean, functional API for building query conditions
 */

/**
 * Create an equality condition
 * Usage: where(eq($users->id, 5))
 */
function eq(Column $column, mixed $value): EqCondition
{
    return new EqCondition($column, $value);
}

/**
 * Create a not equal condition
 * Usage: where(ne($users->status, 'inactive'))
 */
function ne(Column $column, mixed $value): NeCondition
{
    return new NeCondition($column, $value);
}

/**
 * Create a greater than condition
 * Usage: where(gt($users->age, 18))
 */
function gt(Column $column, mixed $value): GtCondition
{
    return new GtCondition($column, $value);
}

/**
 * Create a greater than or equal condition
 * Usage: where(gte($users->age, 21))
 */
function gte(Column $column, mixed $value): GteCondition
{
    return new GteCondition($column, $value);
}

/**
 * Create a less than condition
 * Usage: where(lt($users->age, 65))
 */
function lt(Column $column, mixed $value): LtCondition
{
    return new LtCondition($column, $value);
}

/**
 * Create a less than or equal condition
 * Usage: where(lte($users->score, 100))
 */
function lte(Column $column, mixed $value): LteCondition
{
    return new LteCondition($column, $value);
}

/**
 * Create a LIKE condition for pattern matching
 * Usage: where(like($users->name, '%john%'))
 */
function like(Column $column, string $pattern): LikeCondition
{
    return new LikeCondition($column, $pattern);
}

/**
 * Create an AND condition combining two conditions
 * Usage: where(and_(eq($users->active, true), gt($users->age, 18)))
 */
function and_(Condition $left, Condition $right): AndCondition
{
    return new AndCondition($left, $right);
}

/**
 * Create an OR condition combining two conditions
 * Usage: where(or_(eq($users->role, 'admin'), eq($users->role, 'moderator')))
 */
function or_(Condition $left, Condition $right): OrCondition
{
    return new OrCondition($left, $right);
}

/**
 * Create an IN condition for multiple values
 * Usage: where(in($users->status, ['active', 'pending']))
 */
function in(Column $column, array $values): InCondition
{
    return new InCondition($column, $values);
}

/**
 * Create a NOT IN condition
 * Usage: where(notIn($users->status, ['banned', 'deleted']))
 */
function notIn(Column $column, array $values): NotInCondition
{
    return new NotInCondition($column, $values);
}

/**
 * Create an IS NULL condition
 * Usage: where(isNull($users->deletedAt))
 */
function isNull(Column $column): IsNullCondition
{
    return new IsNullCondition($column);
}

/**
 * Create an IS NOT NULL condition
 * Usage: where(isNotNull($users->emailVerifiedAt))
 */
function isNotNull(Column $column): IsNotNullCondition
{
    return new IsNotNullCondition($column);
}

/**
 * Create a BETWEEN condition
 * Usage: where(between($users->age, 18, 65))
 */
function between(Column $column, mixed $min, mixed $max): BetweenCondition
{
    return new BetweenCondition($column, $min, $max);
}

/**
 * Create a NOT condition (negation)
 * Usage: where(not(eq($users->status, 'banned')))
 */
function not(Condition $condition): NotCondition
{
    return new NotCondition($condition);
}
