<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Limitation;
use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState as SPIObjectState;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation as APINewObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

/**
 * NewObjectStateLimitationType is a Content Limitation used on 'state' 'assign' function.
 */
class NewObjectStateLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     */
    public function acceptValue(APILimitationValue $limitationValue): void
    {
        if (!$limitationValue instanceof APINewObjectStateLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'NewObjectStateLimitation', $limitationValue);
        } elseif (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType('$limitationValue->limitationValues', 'array', $limitationValue->limitationValues);
        }

        foreach ($limitationValue->limitationValues as $key => $id) {
            if (!is_string($id) && !is_int($id)) {
                throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'int|string', $id);
            }
        }
    }

    /**
     * Makes sure LimitationValue->limitationValues is valid according to valueSchema().
     *
     * Make sure {@link acceptValue()} is checked first!
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(APILimitationValue $limitationValue): array
    {
        $validationErrors = [];
        foreach ($limitationValue->limitationValues as $key => $id) {
            try {
                $this->persistence->objectStateHandler()->load($id);
            } catch (APINotFoundException $e) {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' does not exist in the backend",
                    null,
                    [
                        'value' => $id,
                        'key' => $key,
                    ]
                );
            }
        }

        return $validationErrors;
    }

    /**
     * Create the Limitation Value.
     *
     * @param mixed[] $limitationValues
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Limitation
     */
    public function buildValue(array $limitationValues): APILimitationValue
    {
        return new APINewObjectStateLimitation(['limitationValues' => $limitationValues]);
    }

    /**
     * Evaluate permission against content & target(placement/parent/assignment).
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     * @param object $object
     * @param object[]|null $targets The context of the $object, like Location of Content, if null none where provided by caller
     *
     * @return bool|null
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1,  2 ]
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     */
    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, object $object, array $targets = null): ?bool
    {
        if (!$value instanceof APINewObjectStateLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: NewObjectStateLimitation');
        }

        if (!$object instanceof ContentInfo && !$object instanceof Content && !$object instanceof VersionInfo) {
            throw new InvalidArgumentException('$object', 'Must be of type: Content, VersionInfo or ContentInfo');
        }

        if (empty($targets)) {
            throw new InvalidArgumentException('$targets', 'Must contain ObjectState objects');
        }

        if (empty($value->limitationValues)) {
            return false;
        }

        foreach ($targets as $target) {
            if (!$target instanceof ObjectState && !$target instanceof SPIObjectState) {
                throw new InvalidArgumentException('$targets', 'Must contain ObjectState objects');
            }

            if (!in_array($target->id, $value->limitationValues)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns Criterion for use in find() query.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException Not applicable, needs context of new state.
     */
    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser): CriterionInterface
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * Returns info on valid $limitationValues.
     *
     * @return int|mixed[] In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_ constants.
     */
    public function valueSchema(): array|int
    {
        throw new NotImplementedException(__METHOD__);
    }
}
