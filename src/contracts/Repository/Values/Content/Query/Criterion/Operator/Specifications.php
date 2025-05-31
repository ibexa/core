<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;

/**
 * This class is used by Criteria to describe which operators they support.
 *
 * Instances of this class are returned in an array by the {@see \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion::getSpecifications()} method.
 */
class Specifications
{
    /** Criterion input type description constants: single. */
    public const int FORMAT_SINGLE = 1;
    /** Criterion input type description constants: array. */
    public const int FORMAT_ARRAY = 2;

    /** Criterion input value type description constants: integer. */
    public const int TYPE_INTEGER = 1;
    /** Criterion input value type description constants: string. */
    public const int TYPE_STRING = 2;
    /** Criterion input value type description constants: boolean. */
    public const int TYPE_BOOLEAN = 4;

    /**
     * Specified operator, as one of the Operator::* constants.
     */
    public string $operator;

    /**
     * Format supported for the Criterion value.
     *
     * Either {@see Specifications::FORMAT_SINGLE} for single
     * or {@see Specifications::FORMAT_ARRAY} for multiple.
     *
     * @see Specifications::FORMAT_SINGLE
     * @see Specifications::FORMAT_ARRAY
     */
    public int $valueFormat;

    /**
     * Accepted values types, specifying what type of variables are accepted as a value.
     *
     * @see Specifications::TYPE_INTEGER
     * @see Specifications::TYPE_STRING
     * @see Specifications::TYPE_BOOLEAN
     */
    public ?int $valueTypes;

    /**
     * Limitation on the number of items as the value.
     *
     * Only usable if {@see Specifications::$valueFormat} is {@see Specifications::FORMAT_ARRAY}.
     * Not setting it means that 1...n will be required
     *
     * @see Specifications::$valueFormat
     * @see Specifications::FORMAT_ARRAY
     */
    public ?int $valueCount;

    /**
     * Creates a new Specifications object.
     *
     * @param string $operator The specified operator, as one of the {@see \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator}::* constants
     * @param int $valueFormat The accepted value format, either {@see Specifications::FORMAT_ARRAY} or {@see Specifications::FORMAT_SINGLE}
     * @param int|null $valueTypes The supported value types, as a bit field of the self::TYPE_* constants
     * @param int|null $valueCount The required number of values, when the accepted format is {@see Specifications::FORMAT_ARRAY}
     *
     * @see Specifications::FORMAT_SINGLE
     * @see Specifications::FORMAT_ARRAY
     * @see Specifications::TYPE_INTEGER
     * @see Specifications::TYPE_STRING
     * @see Specifications::TYPE_BOOLEAN
     */
    public function __construct(string $operator, int $valueFormat, ?int $valueTypes = null, ?int $valueCount = null)
    {
        $this->operator = $operator;
        $this->valueFormat = $valueFormat;
        $this->valueTypes = $valueTypes;
        $this->valueCount = $valueCount;
    }
}
