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
 * A criterion that matches content based on one of the user metadata (owner,
 * group, modifier).
 *
 * Supported Operators:
 * - {@see Operator::EQ EQ}, {@see Operator::IN IN}: Matches the provided user ID(s) against the user IDs in the database.
 *
 * The following example is a criterion for contents owned by a user with ID 10 or 14:
 * ```
 * $createdCriterion = new Criterion\UserMetadata(
 *     Criterion\UserMetadata::OWNER,
 *     Criterion\Operator::IN,
 *     [10, 14]
 * );
 * ```
 */
class UserMetadata extends Criterion implements TrashCriterion, FilteringCriterion
{
    /**
     * UserMetadata target: Owner user.
     */
    public const string OWNER = 'owner';

    /**
     * UserMetadata target: Owner user group.
     */
    public const string GROUP = 'group';

    /**
     * UserMetadata target: Modifier.
     */
    public const string MODIFIER = 'modifier';

    /**
     * Creates a new UserMetadata criterion.
     *
     * @throws InvalidArgumentException If target is unknown
     *
     * @param string $target One of {@see UserMetadata::OWNER}, {@see UserMetadata::GROUP}, or {@see UserMetadata::MODIFIER}.
     * @param string|null $operator The operator the Criterion uses. If null is given, will default to {@see Operator::IN} if $value is an array, {@see Operator::EQ} if it isn't.
     * @param int|int[] $value The match value, either as an array of as a single value, depending on the operator.
     */
    public function __construct(
        string $target,
        ?string $operator,
        int | array $value
    ) {
        switch ($target) {
            case self::OWNER:
            case self::GROUP:
            case self::MODIFIER:
                parent::__construct($target, $operator, $value);

                return;
        }

        throw new InvalidArgumentException("Unknown UserMetadata $target");
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_INTEGER
            ),
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_INTEGER
            ),
        ];
    }
}
