<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation as APIObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;
use RuntimeException;

/**
 * ObjectStateLimitation is a Content limitation.
 */
class ObjectStateLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
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
        if (!$limitationValue instanceof APIObjectStateLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APIObjectStateLimitation', $limitationValue);
        }

        if (!is_array($limitationValue->limitationValues)) {
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
        return new APIObjectStateLimitation(['limitationValues' => $limitationValues]);
    }

    /**
     * Evaluate permission against content & target(placement/parent/assignment).
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $object
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject[]|null $targets An array of location, parent or "assignment" value objects
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
        ValueObject $object,
        array $targets = null
    ): ?bool {
        if (!$value instanceof APIObjectStateLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APIObjectStateLimitation');
        }

        if (empty($value->limitationValues)) {
            return false;
        }
        $limitationValues = array_map('intval', $value->limitationValues);

        $objectStateHandler = $this->persistence->objectStateHandler();
        if (!$object instanceof ContentCreateStruct) {
            $contentInfo = $this->getContentInfo($object);
            // Skip evaluating for RootLocation
            if (1 === $contentInfo->mainLocationId) {
                return true;
            }

            $objectStateIdsToVerify = $this->getObjectStateIdsToVerify(
                $objectStateHandler,
                $contentInfo,
                $limitationValues
            );
        } else {
            @trigger_error(
                'Passing ContentCreateStruct to ObjectStateLimitationType is deprecated ' .
                ' and will throw an error in Ibexa 5.0',
                E_USER_DEPRECATED
            );

            $objectStateIdsToVerify = $this->getObjectStateIdsForContentCreate(
                $objectStateHandler,
                $limitationValues
            );
        }

        return $this->areObjectStatesMatchingTheLimitation($objectStateIdsToVerify, $limitationValues);
    }

    /**
     * Checks if the State Group contains at least one State that is used by Limitation.
     *
     * @param int $stateGroupId
     * @param string[] $limitationValues
     *
     * @return bool
     */
    private function isStateGroupUsedForLimitation($stateGroupId, array $limitationValues): bool
    {
        $objectStateHandler = $this->persistence->objectStateHandler();
        $states = $objectStateHandler->loadObjectStates($stateGroupId);

        foreach ($states as $state) {
            // check using loose types as limitation values are strings and id's can be int
            if (in_array($state->id, $limitationValues)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifies if all the Object States are matching the Limitation Values.
     *
     * @param int[] $objectStateIds
     * @param string[] $limitationValues
     *
     * @return bool
     */
    private function areObjectStatesMatchingTheLimitation(
        array $objectStateIds,
        array $limitationValues
    ): bool {
        foreach ($objectStateIds as $objectStateId) {
            if (!in_array($objectStateId, $limitationValues, true)) {
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
     */
    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser): CriterionInterface
    {
        if (empty($value->limitationValues)) {
            // A Policy should not have empty limitationValues stored
            throw new RuntimeException('$value->limitationValues is empty');
        }

        if (!isset($value->limitationValues[1])) {
            // 1 limitation value: EQ operation
            return new Criterion\ObjectStateId($value->limitationValues[0]);
        }

        $groupedLimitationValues = $this->groupLimitationValues($value->limitationValues);

        if (count($groupedLimitationValues) === 1) {
            // one group, several limitation values: IN operation
            return new Criterion\ObjectStateId($groupedLimitationValues[0]);
        }

        // limitations from different groups require logical AND between them
        $criterions = [];
        foreach ($groupedLimitationValues as $limitationGroup) {
            $criterions[] = new Criterion\ObjectStateId($limitationGroup);
        }

        return new Criterion\LogicalAnd($criterions);
    }

    /**
     * Groups limitation values by the State Group.
     *
     * @param string[] $limitationValues
     *
     * @return int[][]
     */
    private function groupLimitationValues(array $limitationValues)
    {
        $objectStateHandler = $this->persistence->objectStateHandler();
        $stateGroups = $objectStateHandler->loadAllGroups();
        $groupedLimitationValues = [];
        foreach ($stateGroups as $stateGroup) {
            $states = $objectStateHandler->loadObjectStates($stateGroup->id);
            $stateIds = array_map(static function ($state) {
                return $state->id;
            }, $states);
            $limitationValuesGroup = array_intersect($stateIds, $limitationValues);
            if (!empty($limitationValuesGroup)) {
                $groupedLimitationValues[] = array_values($limitationValuesGroup);
            }
        }

        return $groupedLimitationValues;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function valueSchema(): array|int
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getContentInfo(ValueObject $object): ContentInfo
    {
        if ($object instanceof Content) {
            $object = $object->getVersionInfo()->getContentInfo();
        } elseif ($object instanceof VersionInfo) {
            $object = $object->getContentInfo();
        } elseif (!$object instanceof ContentInfo) {
            throw new InvalidArgumentException(
                '$object',
                'Must be of type: Content, VersionInfo, ContentInfo, or ContentCreateStruct'
            );
        }

        return $object;
    }

    /**
     * @param int[] $limitationValues Limitation Object State IDs
     *
     * @return int[] Object State IDs to verify
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function getObjectStateIdsForContentCreate(
        Handler $objectStateHandler,
        array $limitationValues
    ): array {
        $objectStateIdsToVerify = [];
        $stateGroups = $objectStateHandler->loadAllGroups();
        foreach ($stateGroups as $stateGroup) {
            $states = $objectStateHandler->loadObjectStates($stateGroup->id);
            if (empty($states)) {
                continue;
            }

            $defaultStateId = $this->getDefaultStateId($states, $stateGroup);

            foreach ($states as $state) {
                // check using loose types as limitation values are strings and id's can be int
                if (in_array($state->id, $limitationValues, true)) {
                    $objectStateIdsToVerify[] = $defaultStateId;
                }
            }
        }

        return $objectStateIdsToVerify;
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\ObjectState[] $states
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function getDefaultStateId(array $states, Group $stateGroup): int
    {
        $defaultStateId = null;
        $defaultStatePriority = -1;
        foreach ($states as $state) {
            if ($state->priority > $defaultStatePriority) {
                $defaultStateId = $state->id;
                $defaultStatePriority = $state->priority;
            }
        }

        if ($defaultStateId === null) {
            throw new BadStateException(
                '$defaultStateId',
                "Could not find a default state for object state group {$stateGroup->id}"
            );
        }

        return $defaultStateId;
    }

    private function getObjectStateIdsToVerify(
        Handler $objectStateHandler,
        ContentInfo $object,
        array $limitationValues
    ): array {
        $objectStateIdsToVerify = [];
        $stateGroups = $objectStateHandler->loadAllGroups();
        foreach ($stateGroups as $stateGroup) {
            if ($this->isStateGroupUsedForLimitation($stateGroup->id, $limitationValues)) {
                $objectStateIdsToVerify[] = $objectStateHandler->getContentState(
                    $object->id,
                    $stateGroup->id
                )->id;
            }
        }

        return $objectStateIdsToVerify;
    }
}
