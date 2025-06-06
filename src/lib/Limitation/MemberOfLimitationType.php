<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\MemberOfLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Contracts\Core\Repository\Values\User\UserRoleAssignment;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

final class MemberOfLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    public const SELF_USER_GROUP = -1;

    /**
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function acceptValue(APILimitationValue $limitationValue): void
    {
        if (!$limitationValue instanceof MemberOfLimitation) {
            throw new InvalidArgumentType(
                '$limitationValue',
                MemberOfLimitation::class,
                $limitationValue
            );
        }

        if (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType(
                '$limitationValue->limitationValues',
                'array',
                $limitationValue->limitationValues
            );
        }

        foreach ($limitationValue->limitationValues as $key => $id) {
            if (!is_int($id)) {
                throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'int|string', $id);
            }
        }
    }

    public function validate(APILimitationValue $limitationValue): array
    {
        $validationErrors = [];

        foreach ($limitationValue->limitationValues as $key => $id) {
            if ($id === self::SELF_USER_GROUP) {
                continue;
            }
            try {
                $this->persistence->contentHandler()->loadContentInfo($id);
            } catch (NotFoundException $e) {
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
     * @param mixed[] $limitationValues
     */
    public function buildValue(array $limitationValues): APILimitationValue
    {
        return new MemberOfLimitation(['limitationValues' => $limitationValues]);
    }

    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, object $object, array $targets = null): ?bool
    {
        if (!$value instanceof MemberOfLimitation) {
            throw new InvalidArgumentException(
                '$value',
                sprintf('Must be of type: %s', MemberOfLimitation::class)
            );
        }

        if (!$object instanceof User
            && !$object instanceof UserGroup
            && !$object instanceof UserRoleAssignment
            && !$object instanceof UserGroupRoleAssignment
        ) {
            return self::ACCESS_ABSTAIN;
        }

        if ($object instanceof User) {
            return $this->evaluateUser($value, $object, $currentUser);
        }

        if ($object instanceof UserGroup) {
            return $this->evaluateUserGroup($value, $object, $currentUser);
        }

        if ($object instanceof UserRoleAssignment) {
            return $this->evaluateUser($value, $object->getUser(), $currentUser);
        }

        if ($object instanceof UserGroupRoleAssignment) {
            return $this->evaluateUserGroup($value, $object->getUserGroup(), $currentUser);
        }

        return self::ACCESS_DENIED;
    }

    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser): CriterionInterface
    {
        throw new NotImplementedException('Member of Limitation Criterion');
    }

    public function valueSchema(): array|int
    {
        throw new NotImplementedException(__METHOD__);
    }

    private function evaluateUser(MemberOfLimitation $value, User $object, APIUserReference $currentUser): bool
    {
        if (empty($value->limitationValues)) {
            return self::ACCESS_DENIED;
        }

        $userLocations = $this->persistence->locationHandler()->loadLocationsByContent($object->getUserId());

        $userGroups = [];
        foreach ($userLocations as $userLocation) {
            $userGroups[] = $this->persistence->locationHandler()->load($userLocation->parentId);
        }
        $userGroupsIdList = array_column($userGroups, 'contentId');
        $limitationValuesUserGroupsIdList = $value->limitationValues;

        if (in_array(self::SELF_USER_GROUP, $limitationValuesUserGroupsIdList)) {
            $currentUserGroupsIdList = $this->getCurrentUserGroupsIdList($currentUser);

            // Granted, if current user is in exactly those same groups
            if (count(array_intersect($userGroupsIdList, $currentUserGroupsIdList)) === count($userGroupsIdList)) {
                return self::ACCESS_GRANTED;
            }

            // Unset SELF value, for next check
            $key = array_search(self::SELF_USER_GROUP, $limitationValuesUserGroupsIdList);
            unset($limitationValuesUserGroupsIdList[$key]);
        }

        // Granted, if limitationValues matched user groups 1:1
        if (!empty($limitationValuesUserGroupsIdList)
            && empty(array_diff($userGroupsIdList, $limitationValuesUserGroupsIdList))
        ) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }

    private function evaluateUserGroup(MemberOfLimitation $value, UserGroup $userGroup, APIUserReference $currentUser): bool
    {
        $limitationValuesUserGroupsIdList = $value->limitationValues;
        if (in_array(self::SELF_USER_GROUP, $limitationValuesUserGroupsIdList)) {
            $limitationValuesUserGroupsIdList = $this->getCurrentUserGroupsIdList($currentUser);
        }

        return in_array($userGroup->id, $limitationValuesUserGroupsIdList);
    }

    private function getCurrentUserGroupsIdList(APIUserReference $currentUser): array
    {
        $currentUserLocations = $this->persistence->locationHandler()->loadLocationsByContent($currentUser->getUserId());
        $currentUserGroups = [];
        foreach ($currentUserLocations as $currentUserLocation) {
            $currentUserGroups[] = $this->persistence->locationHandler()->load($currentUserLocation->parentId);
        }

        return array_column(
            $currentUserGroups,
            'contentId'
        );
    }
}
