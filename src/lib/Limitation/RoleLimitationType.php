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
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\UserRoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Contracts\Core\Repository\Values\User\UserRoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

final class RoleLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    /**
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function acceptValue(APILimitationValue $limitationValue): void
    {
        if (!$limitationValue instanceof UserRoleLimitation) {
            throw new InvalidArgumentType(
                '$limitationValue',
                UserRoleLimitation::class,
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
                throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'int', $id);
            }
        }
    }

    public function validate(APILimitationValue $limitationValue): array
    {
        $validationErrors = [];

        foreach ($limitationValue->limitationValues as $key => $id) {
            try {
                $this->persistence->userHandler()->loadRole($id);
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
        return new UserRoleLimitation(['limitationValues' => $limitationValues]);
    }

    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, ValueObject $object, array $targets = null): ?bool
    {
        if (!$value instanceof UserRoleLimitation) {
            throw new InvalidArgumentException(
                '$value',
                sprintf('Must be of type: %s', UserRoleLimitation::class)
            );
        }

        if (
            !$object instanceof Role
            && !$object instanceof UserRoleAssignment
            && !$object instanceof UserGroupRoleAssignment
            && ($targets === null && ($object instanceof User || $object instanceof UserGroup))
        ) {
            return self::ACCESS_ABSTAIN;
        }

        if ($targets !== null) {
            foreach ($targets as $target) {
                if ($target instanceof Role && !$this->evaluateRole($value, $target)) {
                    return self::ACCESS_DENIED;
                }

                return self::ACCESS_GRANTED;
            }
        }

        if ($object instanceof Role) {
            return $this->evaluateRole($value, $object);
        }

        if ($object instanceof UserRoleAssignment || $object instanceof UserGroupRoleAssignment) {
            return $this->evaluateRole($value, $object->getRole());
        }

        return self::ACCESS_DENIED;
    }

    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser): CriterionInterface
    {
        throw new NotImplementedException('Role Limitation Criterion');
    }

    public function valueSchema(): array|int
    {
        throw new NotImplementedException(__METHOD__);
    }

    private function evaluateRole(UserRoleLimitation $value, Role $role): bool
    {
        return in_array($role->id, $value->limitationValues);
    }
}
