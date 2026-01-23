<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Limitation;
use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentContentTypeLimitation as APIParentContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

/**
 * ParentContentTypeLimitation is a Content limitation.
 */
class ParentContentTypeLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
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
        if (!$limitationValue instanceof APIParentContentTypeLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APIParentContentTypeLimitation', $limitationValue);
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
     * @param APILimitationValue $limitationValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(APILimitationValue $limitationValue): array
    {
        $validationErrors = [];
        foreach ($limitationValue->limitationValues as $key => $id) {
            try {
                $this->persistence->contentTypeHandler()->load($id);
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
     * @return APILimitationValue
     */
    public function buildValue(array $limitationValues): APILimitationValue
    {
        return new APIParentContentTypeLimitation(['limitationValues' => $limitationValues]);
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
     *@throws BadStateException If value of the LimitationValue is unsupported
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
        if (!$value instanceof APIParentContentTypeLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APIParentContentTypeLimitation');
        }

        if ($object instanceof ContentCreateStruct) {
            return $this->evaluateForContentCreateStruct($value, $targets);
        } elseif ($object instanceof Content) {
            $object = $object->getVersionInfo()->getContentInfo();
        } elseif ($object instanceof VersionInfo) {
            $object = $object->getContentInfo();
        } elseif (!$object instanceof ContentInfo) {
            throw new InvalidArgumentException(
                '$object',
                'Must be of type: ContentCreateStruct, Content, VersionInfo or ContentInfo'
            );
        }

        // Try to load locations if no targets were provided
        if (empty($targets)) {
            if ($object->published) {
                $targets = $this->loadParentLocations($object);
            } else {
                // @todo Need support for draft locations to to work correctly
                $targets = $this->persistence->locationHandler()->loadParentLocationsForDraftContent($object->id);
            }
        }

        // If targets is empty/null return false as user does not have access
        // to content w/o location with this limitation
        if (empty($targets)) {
            return false;
        }

        foreach ($targets as $target) {
            if ($target instanceof LocationCreateStruct) {
                $target = $this->persistence->locationHandler()->load($target->parentLocationId);
            }

            if ($target instanceof Location) {
                $contentTypeId = $target->getContentInfo()->contentTypeId;
            } elseif ($target instanceof SPILocation) {
                $spiContentInfo = $this->persistence->contentHandler()->loadContentInfo($target->contentId);
                $contentTypeId = $spiContentInfo->contentTypeId;
            } else {
                throw new InvalidArgumentException(
                    '$targets',
                    'Must contain Location or LocationCreateStruct objects'
                );
            }

            if (!in_array($contentTypeId, $value->limitationValues)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate permissions for ContentCreateStruct against LocationCreateStruct placements.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If $targets does not contain
     *         objects of type LocationCreateStruct
     * @throws NotFoundException
     *
     * @param APILimitationValue $value
     * @param array $targets
     *
     * @return bool
     */
    protected function evaluateForContentCreateStruct(
        APILimitationValue $value,
        ?array $targets = null
    ): bool {
        // If targets is empty/null return false as user does not have access
        // to content w/o location with this limitation
        if (empty($targets)) {
            return false;
        }

        $hasMandatoryTarget = false;
        foreach ($targets as $target) {
            if ($target instanceof LocationCreateStruct) {
                $hasMandatoryTarget = true;
                $location = $this->persistence->locationHandler()->load($target->parentLocationId);
                $contentTypeId = $this->persistence->contentHandler()->loadContentInfo($location->contentId)->contentTypeId;

                if (!in_array($contentTypeId, $value->limitationValues)) {
                    return false;
                }
            }
        }

        if (false === $hasMandatoryTarget) {
            throw new InvalidArgumentException(
                '$targets',
                'If $object is ContentCreateStruct, it must contain LocationCreateStruct objects'
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

    /**
     * @param ContentInfo $contentInfo
     *
     * @return SPILocation[]
     *
     * @throws NotFoundException
     */
    private function loadParentLocations(ContentInfo $contentInfo)
    {
        $locations = $this->persistence->locationHandler()->loadLocationsByContent($contentInfo->id);
        $parentLocations = [];
        foreach ($locations as $location) {
            if ($location->depth > 0) {
                $parentLocations[] = $this->persistence->locationHandler()->load($location->parentId);
            }
        }

        return $parentLocations;
    }
}
