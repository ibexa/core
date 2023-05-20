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
 * Instances of this class are returned in an array by the Criterion::getSpecifications() method
 *
 * @see \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion::getSpecifications()
 */
class Specifications
{
    /** Criterion input type description constants: single. */
    public const FORMAT_SINGLE = 1;
    /** Criterion input type description constants: array. */
    public const FORMAT_ARRAY = 2;

    /** Criterion input value type description constants: integer. */
    public const TYPE_INTEGER = 1;
    /** Criterion input value type description constants: string. */
    public const TYPE_STRING = 2;
    /** Criterion input value type description constants: boolean. */
    public const TYPE_BOOLEAN = 4;

    /**
     * Specified operator, as one of the Operator::* constants.
     */
    public $operator;

    /**
     * Format supported for the Criterion value.
     *
     * Either {@see Specifications::FORMAT_SINGLE} for single
     * or {@see Specifications::FORMAT_ARRAY} for multiple.
     *
     * @see Specifications::FORMAT_SINGLE
     * @see Specifications::FORMAT_ARRAY
     *
     * @var int
     */
    public $valueFormat;

    /**
     * Accepted values types, specifying what type of variables are accepted as a value.
     *
     * @see Specifications::TYPE_INTEGER
     * @see Specifications::TYPE_STRING
     * @see Specifications::TYPE_BOOLEAN
     *
     * @var int
     */
    public $valueTypes;

    /**
     * Limitation on the number of items as the value.
     *
     * Only usable if {@see Specifications::$valueFormat} is {@see Specifications::FORMAT_ARRAY}.
     * Not setting it means that 1...n will be required
     *
     * @see Specifications::$valueFormat
     * @see Specifications::FORMAT_ARRAY
     *
     * @var int
     */
    public $valueCount;

    /**
     * Creates a new Specifications object.
     *
     * @param string $operator The specified operator, as one of the {@see \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator}::* constants
     * @param int $valueFormat The accepted value format, either {@see Specifications::FORMAT_ARRAY} or {@see Specifications::FORMAT_SINGLE}
     * @param int $valueTypes The supported value types, as a bit field of the self::TYPE_* constants
     * @param int $valueCount The required number of values, when the accepted format is {@see Specifications::FORMAT_ARRAY}
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

class_alias(Specifications::class, 'eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications');
