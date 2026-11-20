<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command\Role;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\Role;

/**
 * @internal
 */
final class UnresolvedLimitationValuesFinding
{
    private Role $role;

    private Policy $policy;

    private Limitation $limitation;

    /** @var array<array{key: string|null, value: mixed}> */
    private array $unresolvedValues;

    /**
     * @param array<array{key: string|null, value: mixed}> $unresolvedValues each value, and the key it
     *        sits under for Limitations whose values are a map of lists rather than a plain list
     */
    public function __construct(
        Role $role,
        Policy $policy,
        Limitation $limitation,
        array $unresolvedValues
    ) {
        $this->role = $role;
        $this->policy = $policy;
        $this->limitation = $limitation;
        $this->unresolvedValues = $unresolvedValues;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getPolicy(): Policy
    {
        return $this->policy;
    }

    public function getLimitation(): Limitation
    {
        return $this->limitation;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->unresolvedValues as $unresolved) {
            $labels[] = $unresolved['key'] === null
                ? (string)$unresolved['value']
                : sprintf('%s: %s', $unresolved['key'], $unresolved['value']);
        }

        return $labels;
    }

    /**
     * The Limitation's values with the unresolved ones removed, in the same shape as they came in.
     *
     * @return array<mixed>
     */
    public function getPrunedValues(): array
    {
        $pruned = $this->limitation->limitationValues;

        foreach ($this->unresolvedValues as $unresolved) {
            if ($unresolved['key'] === null) {
                $pruned = array_values(array_filter(
                    $pruned,
                    static function ($value) use ($unresolved): bool {
                        return (string)$value !== (string)$unresolved['value'];
                    }
                ));

                continue;
            }

            if (!isset($pruned[$unresolved['key']]) || !is_array($pruned[$unresolved['key']])) {
                continue;
            }

            $pruned[$unresolved['key']] = array_values(array_filter(
                $pruned[$unresolved['key']],
                static function ($value) use ($unresolved): bool {
                    return (string)$value !== (string)$unresolved['value'];
                }
            ));
        }

        return $pruned;
    }

    public function emptiesLimitation(): bool
    {
        return self::holdsNoValues($this->getPrunedValues());
    }

    /**
     * True when neither repair keeps what the Policy grants, so it has to be left to a human.
     *
     * Only Limitations holding lists of values under keys, such as UserPermissions, can get here.
     * For each key that limitation reads an empty list as "no restriction on that dimension", so a list
     * that held only unresolved values cannot be pruned — emptying it grants everything there — and
     * a list that was empty to begin with is already granting everything, which is lost if the
     * Policy is removed instead. Both at once leaves nothing that preserves the current grants:
     *
     * - "roles" [15] with 15 gone, "user_groups" [11]: pruning empties "roles" and would grant
     *   every Role, removing the Policy takes away the "user_groups" [11] grant.
     * - "roles" [15] with 15 gone, "user_groups" []: same, except the lost grant is every Content.
     *
     * Where every list holds only unresolved values the Policy grants nothing as it stands, so
     * removing it is neutral and this returns false.
     */
    public function cannotPreserveGrants(): bool
    {
        $limitationValues = $this->limitation->limitationValues;
        if (array_values($limitationValues) === $limitationValues) {
            return false;
        }

        $pruned = $this->getPrunedValues();

        $losesRestriction = false;
        $grantsSomething = false;
        foreach ($limitationValues as $key => $values) {
            if (!is_array($values) || !is_array($pruned[$key] ?? null)) {
                continue;
            }

            if ($values !== [] && $pruned[$key] === []) {
                $losesRestriction = true;
            } elseif ($values === [] || $pruned[$key] !== []) {
                $grantsSomething = true;
            }
        }

        return $losesRestriction && $grantsSomething;
    }

    /**
     * @param array<mixed> $limitationValues
     */
    public static function holdsNoValues(array $limitationValues): bool
    {
        foreach ($limitationValues as $value) {
            if (is_array($value)) {
                if ($value !== []) {
                    return false;
                }

                continue;
            }

            if ($value !== null) {
                return false;
            }
        }

        return true;
    }
}
