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
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentUserGroupLimitation as APIParentUserGroupLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

/**
 * ParentUserGroupLimitation is a Content limitation.
 */
class ParentUserGroupLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @param APILimitationValue $limitationValue
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     */
    public function acceptValue(APILimitationValue $limitationValue): void
    {
        if (!$limitationValue instanceof APIParentUserGroupLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APIParentUserGroupLimitation', $limitationValue);
        } elseif (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType('$limitationValue->limitationValues', 'array', $limitationValue->limitationValues);
        }

        foreach ($limitationValue->limitationValues as $key => $value) {
            // Accept a true value for b/c with 5.0
            if ($value === true) {
                $limitationValue->limitationValues[$key] = 1;
            } elseif (is_string($value) && ctype_digit($value)) {
                // Cast integers passed as string to int
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
     * @param APILimitationValue $limitationValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(APILimitationValue $limitationValue): array
    {
        $validationErrors = [];
        foreach ($limitationValue->limitationValues as $key => $value) {
            if ($value !== 1) {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' must be 1 (owner)",
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
     * @return APILimitationValue
     */
    public function buildValue(array $limitationValues): APILimitationValue
    {
        return new APIParentUserGroupLimitation(['limitationValues' => $limitationValues]);
    }

    /**
     * Evaluate permission against content & target(placement/parent/assignment).
     *
     * @param APILimitationValue $value
     * @param UserReference $currentUser
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
    public function evaluate(
        APILimitationValue $value,
        APIUserReference $currentUser,
        object $object,
        ?array $targets = null
    ): ?bool {
        if (!$value instanceof APIParentUserGroupLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APIParentUserGroupLimitation');
        }

        if ($value->limitationValues[0] != 1) {
            throw new BadStateException(
                'Parent User Group limitation',
                'Expected Limitation value to be 1 instead of' . $value->limitationValues[0]
            );
        }

        // Parent Limitations are usually used by content/create where target is specified, so we return false if not provided.
        if (empty($targets)) {
            return false;
        }

        $locationHandler = $this->persistence->locationHandler();
        $currentUserLocations = $locationHandler->loadLocationsByContent($currentUser->getUserId());
        if (empty($currentUserLocations)) {
            return false;
        }

        $hasMandatoryTarget = false;
        foreach ($targets as $target) {
            if ($target instanceof LocationCreateStruct) {
                $hasMandatoryTarget = true;
                $target = $locationHandler->load($target->parentLocationId);
            }

            if ($target instanceof Location) {
                $hasMandatoryTarget = true;
                // $target is assumed to be parent in this case
                $parentOwnerId = $target->getContentInfo()->ownerId;
            } elseif ($target instanceof SPILocation) {
                $hasMandatoryTarget = true;
                // $target is assumed to be parent in this case
                $spiContentInfo = $this->persistence->contentHandler()->loadContentInfo($target->contentId);
                $parentOwnerId = $spiContentInfo->ownerId;
            } else {
                continue;
            }

            if ($parentOwnerId === $currentUser->getUserId()) {
                continue;
            }

            /*
             * As long as SPI userHandler and API UserService does not speak the same language, this is the ugly truth;
             */
            $locationHandler = $this->persistence->locationHandler();
            $parentOwnerLocations = $locationHandler->loadLocationsByContent($parentOwnerId);
            if (empty($parentOwnerLocations)) {
                return false;
            }

            foreach ($parentOwnerLocations as $parentOwnerLocation) {
                foreach ($currentUserLocations as $currentUserLocation) {
                    if ($parentOwnerLocation->parentId === $currentUserLocation->parentId) {
                        continue 3;
                    }
                }
            }

            return false;
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
     * @param APILimitationValue $value
     * @param UserReference $currentUser
     *
     * @return CriterionInterface
     */
    public function getCriterion(
        APILimitationValue $value,
        APIUserReference $currentUser
    ): CriterionInterface {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * Returns info on valid $limitationValues.
     *
     * @return int|mixed[] In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_ constants.
     */
    public function valueSchema(): array | int
    {
        throw new NotImplementedException(__METHOD__);
    }
}
