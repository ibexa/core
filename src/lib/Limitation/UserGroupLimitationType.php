<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Limitation;
use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\UserGroupLimitation as APIUserGroupLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

/**
 * UserGroupLimitation is a Content Limitation.
 */
class UserGroupLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
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
        if (!$limitationValue instanceof APIUserGroupLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APIUserGroupLimitation', $limitationValue);
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
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
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
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Limitation
     */
    public function buildValue(array $limitationValues): APILimitationValue
    {
        return new APIUserGroupLimitation(['limitationValues' => $limitationValues]);
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
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1 ]
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     */
    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, object $object, ?array $targets = null): ?bool
    {
        if (!$value instanceof APIUserGroupLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APIUserGroupLimitation');
        }

        if ($value->limitationValues[0] != 1) {
            throw new BadStateException(
                'Parent User Group limitation',
                'Expected Limitation value to be 1 instead of' . $value->limitationValues[0]
            );
        }

        if ($object instanceof Content) {
            $object = $object->getVersionInfo()->getContentInfo();
        } elseif ($object instanceof VersionInfo) {
            $object = $object->getContentInfo();
        } elseif (!$object instanceof ContentInfo && !$object instanceof ContentCreateStruct) {
            throw new InvalidArgumentException(
                '$object',
                'Must be of type: ContentCreateStruct, Content, VersionInfo or ContentInfo'
            );
        }

        if ($object->ownerId === $currentUser->getUserId()) {
            return true;
        }

        if ($object->ownerId === null) {
            return false;
        }

        /*
         * As long as SPI userHandler and API UserService does not speak the same language, this is the ugly truth;
         */
        $locationHandler = $this->persistence->locationHandler();
        $ownerLocations = $locationHandler->loadLocationsByContent($object->ownerId);
        if (empty($ownerLocations)) {
            return false;
        }

        $currentUserLocations = $locationHandler->loadLocationsByContent($currentUser->getUserId());
        if (empty($currentUserLocations)) {
            return false;
        }

        // @todo Needs to take care of inherited groups as well when UserHandler gets knowledge about user groups
        foreach ($ownerLocations as $ownerLocation) {
            foreach ($currentUserLocations as $currentUserLocation) {
                if ($ownerLocation->parentId === $currentUserLocation->parentId) {
                    return true;
                }
            }
        }

        return false;
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
        if (empty($value->limitationValues)) {
            // A Policy should not have empty limitationValues stored
            throw new \RuntimeException('$value->limitationValues is empty');
        }

        if ($value->limitationValues[0] != 1) {
            throw new BadStateException(
                'Parent User Group limitation',
                'Expected Limitation value to be 1 instead of' . $value->limitationValues[0]
            );
        }

        $groupIds = [];
        $locationHandler = $this->persistence->locationHandler();
        $currentUserLocations = $locationHandler->loadLocationsByContent($currentUser->getUserId());
        foreach ($currentUserLocations as $currentUserLocation) {
            try {
                $parentLocation = $locationHandler->load($currentUserLocation->parentId);
                $groupIds[] = $parentLocation->contentId;
            } catch (NotFoundException $e) {
                // there is no need for any action - carrying on with checking other user locations
                continue;
            }
        }

        return new Criterion\UserMetadata(
            Criterion\UserMetadata::GROUP,
            Criterion\Operator::IN,
            $groupIds
        );
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
