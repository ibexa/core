<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Limitation;
use Ibexa\Contracts\Core\Limitation\Target;
use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentOwnerLimitation as APIParentOwnerLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

/**
 * ParentOwnerLimitation is a Content limitation.
 */
class ParentOwnerLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
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
        if (!$limitationValue instanceof APIParentOwnerLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APIParentOwnerLimitation', $limitationValue);
        } elseif (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType('$limitationValue->limitationValues', 'array', $limitationValue->limitationValues);
        }

        foreach ($limitationValue->limitationValues as $key => $value) {
            // Cast integers passed as string to int
            if (is_string($value) && ctype_digit($value)) {
                $limitationValue->limitationValues[$key] = (int)$value;
            } elseif (!is_int($value)) {
                throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'int', $value);
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
        foreach ($limitationValue->limitationValues as $key => $value) {
            if ($value !== 1 && $value !== 2) {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' must be either 1 (owner) or 2 (session)",
                    null,
                    [
                        'value' => $value,
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
        return new APIParentOwnerLimitation(['limitationValues' => $limitationValues]);
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1,  2 ]
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     *
     * @todo Add support for $limitationValues[0] == 2 when session values can be injected somehow
     */
    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, object $object, array $targets = null): ?bool
    {
        if (!$value instanceof APIParentOwnerLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APIParentOwnerLimitation');
        }

        if ($value->limitationValues[0] != 1 && $value->limitationValues[0] != 2) {
            throw new BadStateException(
                'Parent Owner limitation',
                'Expected Limitation value to be 1 or 2 instead of' . $value->limitationValues[0]
            );
        }

        // Parent Limitations are usually used by content/create where target is specified, so we return false if not provided.
        if (empty($targets)) {
            return false;
        }

        $hasMandatoryTarget = false;
        foreach ($targets as $target) {
            if ($target instanceof LocationCreateStruct) {
                $hasMandatoryTarget = true;
                $target = $this->persistence->locationHandler()->load($target->parentLocationId);
            }

            if ($target instanceof Location) {
                $hasMandatoryTarget = true;
                $targetContentInfo = $target->getContentInfo();
            } elseif ($target instanceof SPILocation) {
                $hasMandatoryTarget = true;
                $targetContentInfo = $this->persistence->contentHandler()->loadContentInfo($target->contentId);
            } else {
                continue;
            }

            $userId = $currentUser->getUserId();

            $isOwner = $targetContentInfo->ownerId === $userId;
            $isSelf = $targetContentInfo->id === $userId;

            if (!($isOwner || $isSelf)) {
                return false;
            }
        }

        if (false === $hasMandatoryTarget) {
            throw new InvalidArgumentException(
                '$targets',
                'Must contain Location or LocationCreateStruct objects'
            );
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
