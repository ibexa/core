<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Limitation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;

/**
 * This interface represent the Limitation Type.
 *
 * A Limitation is a lot like a Symfony voter, telling the permission system if user has
 * access or not. It consists of a Limitation Value which is persisted, and this Limitation
 * Type which contains the business logic for evaluate ("vote"), as well as accepting and
 * validating the Value object and to generate criteria for content/location searches.
 */
interface Type
{
    /**
     * Access is granted.
     *
     * Constant for return value of {@see Type::evaluate()}.
     *
     * Note: In future version constant values might change to 1, 0 and -1 as used in Symfony.
     */
    public const ?bool ACCESS_GRANTED = true;

    /**
     * The type abstains from voting.
     *
     * Constant for return value of {@see Type::evaluate()}.
     *
     * Returning ACCESS_ABSTAIN must mean that evaluate does not support the provided $object or $targets,
     * this is only supported by role limitations as policy limitations should not allow this.
     *
     * Note: In future version constant values might change to 1, 0 and -1 as used in Symfony.
     */
    public const ?bool ACCESS_ABSTAIN = null;

    /**
     * Access is denied.
     *
     * Constant for return value of {@see Type::evaluate()}.
     *
     * Note: In future version constant values might change to 1, 0 and -1 as used in Symfony.
     */
    public const ?bool ACCESS_DENIED = false;

    /**
     * Limitation's value must be an array of location IDs.
     *
     * Constant for {@see Type::valueSchema()} return values.
     *
     * GUI should typically present option to browse content tree to select limitation value(s).
     */
    public const int VALUE_SCHEMA_LOCATION_ID = 1;

    /**
     * Limitation's value must be an array of location paths.
     *
     * Constant for {@see Type::valueSchema()} return values.
     *
     * GUI should typically present option to browse content tree to select limitation value(s).
     */
    public const int VALUE_SCHEMA_LOCATION_PATH = 2;

    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
     */
    public function acceptValue(APILimitationValue $limitationValue): void;

    /**
     * Makes sure LimitationValue->limitationValues is valid according to valueSchema().
     *
     * Make sure {@see Type::acceptValue()} is checked first.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
     *
     * @return array<int, \Ibexa\Contracts\Core\FieldType\ValidationError>
     */
    public function validate(APILimitationValue $limitationValue): array;

    /**
     * Create the Limitation Value.
     *
     * This is the method to create values as Limitation type needs value knowledge anyway in acceptValue,
     * the reverse relation is provided by means of identifier lookup (Value has identifier, and so does RoleService).
     *
     * @param array<int, mixed> $limitationValues
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Limitation
     */
    public function buildValue(array $limitationValues): APILimitationValue;

    /**
     * Evaluate ("Vote") against a main value object and targets for the context.
     *
     * @param array<int, object>|null $targets An array of location, parent or "assignment"
     *                                                                 objects, if null: none where provided by caller
     *
     * @return bool|null Returns one of ACCESS_* constants, {@see Type::ACCESS_GRANTED}, {@see Type::ACCESS_ABSTAIN}, or {@see Type::ACCESS_DENIED}.
     */
    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, object $object, ?array $targets = null): ?bool;

    /**
     * Returns Criterion for use in find() query.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException If the limitation does not support
     *         being used as a Criterion.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface
     */
    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser): CriterionInterface;

    /**
     * Returns info on valid $limitationValues.
     *
     * @return int|mixed[] In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_* constants.
     *                     Note: The hash might be an instance of Traversable, and not a native php array.
     */
    public function valueSchema(): array|int;
}
