<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Limitation;
use Ibexa\Contracts\Core\Limitation\Target\Version;
use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation as APISubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Values\Content\Query\Criterion\PermissionSubtree;

/**
 * SubtreeLimitation is a Content Limitation & a Role Limitation.
 */
class SubtreeLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
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
        if (!$limitationValue instanceof APISubtreeLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APISubtreeLimitation', $limitationValue);
        } elseif (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType('$limitationValue->limitationValues', 'array', $limitationValue->limitationValues);
        }

        foreach ($limitationValue->limitationValues as $key => $path) {
            if (!is_string($path)) {
                throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'string', $path);
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
        foreach ($limitationValue->limitationValues as $key => $path) {
            try {
                $pathArray = explode('/', trim($path, '/'));
                $subtreeRootLocationId = end($pathArray);
                $spiLocation = $this->persistence->locationHandler()->load($subtreeRootLocationId);
            } catch (APINotFoundException $e) {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' does not exist in the backend",
                    null,
                    [
                        'value' => $path,
                        'key' => $key,
                    ]
                );

                continue;
            }

            if (strpos($spiLocation->pathString, $path) !== 0) {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' does not equal Location's path string: '%path_string%'",
                    null,
                    [
                        'value' => $path,
                        'key' => $key,
                        'path_string' => $spiLocation->pathString,
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
        return new APISubtreeLimitation(['limitationValues' => $limitationValues]);
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
        $targets = $targets ?? [];

        if (!$value instanceof APISubtreeLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APISubtreeLimitation');
        }

        if ($object instanceof ContentCreateStruct) {
            return $this->evaluateForContentCreateStruct($value, $targets);
        } elseif ($object instanceof Content) {
            $object = $object->getVersionInfo()->getContentInfo();
        } elseif ($object instanceof VersionInfo) {
            $object = $object->getContentInfo();
        } elseif (!$object instanceof ContentInfo) {
            // As this is Role limitation we need to signal abstain on unsupported $object
            return self::ACCESS_ABSTAIN;
        }

        $targets = array_filter($targets, static function ($target): bool {
            return !$target instanceof Version;
        });

        // Load locations if no specific placement was provided
        if (empty($targets)) {
            if ($object->isTrashed()) {
                $targets = $this->persistence->locationHandler()->loadLocationsByTrashContent($object->id);
            } elseif ($object->isPublished()) {
                $targets = $this->persistence->locationHandler()->loadLocationsByContent($object->id);
            } else {
                // @todo Need support for draft locations to work correctly
                $targets = $this->persistence->locationHandler()->loadParentLocationsForDraftContent($object->id);
            }
        }

        foreach ($targets as $target) {
            if (!$target instanceof Location && !$target instanceof SPILocation) {
                // As this is Role limitation we need to signal abstain on unsupported $targets
                return self::ACCESS_ABSTAIN;
            }

            foreach ($value->limitationValues as $limitationPathString) {
                if ($target->pathString === $limitationPathString) {
                    return true;
                }
                if (strpos($target->pathString, $limitationPathString) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Evaluate permissions for ContentCreateStruct against LocationCreateStruct placements.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If $targets does not contain
     *         objects of type LocationCreateStruct
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param array $targets
     *
     * @return bool
     */
    protected function evaluateForContentCreateStruct(APILimitationValue $value, array $targets): bool
    {
        // If targets is empty/null return false as user does not have access
        // to content w/o location with this limitation
        if (empty($targets)) {
            return false;
        }

        $hasLocationCreateStruct = false;
        foreach ($targets as $target) {
            if (!$target instanceof LocationCreateStruct) {
                continue;
            }

            $hasLocationCreateStruct = true;
            $target = $this->persistence->locationHandler()->load($target->parentLocationId);

            // For ContentCreateStruct all placements must match
            foreach ($value->limitationValues as $limitationPathString) {
                if ($target->pathString === $limitationPathString) {
                    continue 2;
                }
                if (strpos($target->pathString, $limitationPathString) === 0) {
                    continue 2;
                }
            }

            return false;
        }

        if (false === $hasLocationCreateStruct) {
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
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface
     */
    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser): CriterionInterface
    {
        if (empty($value->limitationValues)) {
            // A Policy should not have empty limitationValues store
            throw new \RuntimeException('$value->limitationValues is empty');
        }

        if (!isset($value->limitationValues[1])) {
            // 1 limitation value: EQ operation
            return new PermissionSubtree($value->limitationValues[0]);
        }

        // several limitation values: IN operation
        return new PermissionSubtree($value->limitationValues);
    }

    public function valueSchema(): array|int
    {
        return self::VALUE_SCHEMA_LOCATION_PATH;
    }
}
