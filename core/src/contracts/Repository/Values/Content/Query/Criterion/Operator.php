<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

/**
 * Operators struct.
 *
 * Note that the method is abstract as there is no point in instantiating it
 */
abstract class Operator
{
    public const string EQ = '=';
    public const string GT = '>';
    public const string GTE = '>=';
    public const string LT = '<';
    public const string LTE = '<=';
    public const string IN = 'in';
    public const string BETWEEN = 'between';

    /**
     * Does a lookup where a the value _can_ contain a "*" (a wildcard) in order to match a pattern.
     *
     * E.g: $criterion->value = "Oper*or";
     */
    public const string LIKE = 'like';
    public const string CONTAINS = 'contains';

    public static function isUnary(string $operator): bool
    {
        return in_array(
            $operator,
            [
                self::EQ,
                self::GT,
                self::GTE,
                self::LT,
                self::LTE,
                self::LIKE,
                self::CONTAINS,
            ],
            true
        );
    }
}
