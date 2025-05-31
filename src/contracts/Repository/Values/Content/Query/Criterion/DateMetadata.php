<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Contracts\Core\Repository\Values\Trash\Query\Criterion as TrashCriterion;
use InvalidArgumentException;

/**
 * A criterion that matches content based on one of the date metadata (created or modified).
 *
 * Supported Operators:
 * - {@see Operator::EQ EQ}, {@see Operator::IN IN}: matches content whose date is or belongs to a list of timestamps.
 * - {@see Operator::GT GT}, {@see Operator::GTE GTE}: matches content whose date is greater than/greater than or equals the given timestamp.
 * - {@see Operator::LT LT}, {@see Operator::LTE LTE}: matches content whose date is lower than/lower than or equals the given timestamp.
 * - {@see Operator::BETWEEN BETWEEN}: matches content whose date is between TWO (included) given timestamps.
 *
 * The following example is a criterion for contents created yesterday or today:
 * ```
 * $createdCriterion = new Criterion\DateMetadata(
 *     Criterion\DateMetadata::CREATED,
 *     Criterion\Operator::GTE,
 *     strtotime('yesterday')
 * );
 * ```
 */
class DateMetadata extends Criterion implements TrashCriterion, FilteringCriterion
{
    public const string MODIFIED = 'modified';

    public const string CREATED = 'created';

    public const string PUBLISHED = 'published';

    /**
     * To search for contents based on when they have been sent to trash.
     *
     * Applies to {@see \Ibexa\Contracts\Core\Repository\TrashService::findTrashItems()} only.
     */
    public const string TRASHED = 'trashed';

    public const array TARGETS = [
        self::MODIFIED,
        self::CREATED,
        self::PUBLISHED,
        self::TRASHED,
    ];

    /**
     * Creates a new DateMetadata criterion.
     *
     * @throws \InvalidArgumentException If target is unknown
     *
     * @param string $target One of {@see DateMetadata::CREATED}, {@see DateMetadata::MODIFIED}, or {@see DateMetadata::TRASHED} (applies to {@see \Ibexa\Contracts\Core\Repository\TrashService::findTrashItems()} only)
     * @param string $operator One of the {@see Operator} constants
     * @param int|int[] $value The match value, either as an array of as a single value, depending on the operator
     */
    public function __construct(string $target, string $operator, int|array $value)
    {
        if (!in_array($target, self::TARGETS)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown DateMetadata target "%s". Expected one of: "%s"',
                $target,
                implode('", "', self::TARGETS),
            ));
        }
        parent::__construct($target, $operator, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::EQ, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::GT, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::GTE, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::LT, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::LTE, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::IN, Specifications::FORMAT_ARRAY, Specifications::TYPE_INTEGER),
            new Specifications(Operator::BETWEEN, Specifications::FORMAT_ARRAY, Specifications::TYPE_INTEGER, 2),
        ];
    }
}
