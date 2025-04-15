<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\User\Role\LimitationHandler;

use Doctrine\DBAL\ArrayParameterType;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Core\Persistence\Legacy\User\Role\LimitationHandler;

/**
 * Limitation Handler.
 *
 * Takes care of Converting a Policy limitation from Legacy value to spi value accepted by API.
 */
class ObjectStateHandler extends LimitationHandler
{
    public const string STATE_GROUP = 'StateGroup_';

    /**
     * Translate API STATE limitation to Legacy StateGroup_<identifier> limitations.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function toLegacy(Policy $policy): void
    {
        if ($policy->limitations !== '*' && isset($policy->limitations[Limitation::STATE])) {
            if ($policy->limitations[Limitation::STATE] === '*') {
                $map = array_fill_keys(array_keys($this->getGroupMap()), '*');
            } else {
                $map = $this->getGroupMap(array_map('intval', $policy->limitations[Limitation::STATE]));
            }
            $policy->limitations += $map;
            unset($policy->limitations[Limitation::STATE]);
        }
    }

    /**
     * Translate Legacy StateGroup_<identifier> limitations to API STATE limitation.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function toSPI(Policy $policy): void
    {
        if ($policy->limitations === '*' || empty($policy->limitations) || is_string($policy->limitations)) {
            return;
        }

        // First iterate to prepare for the range of possible conditions below
        $hasStateGroup = false;
        $allWildCard = true;
        $someWildCard = false;
        $map = [];
        foreach ($policy->limitations as $identifier => $limitationsValues) {
            if (strncmp($identifier, self::STATE_GROUP, 11) === 0) {
                $hasStateGroup = true;
                if ($limitationsValues !== '*') {
                    $allWildCard = false;
                } else {
                    $someWildCard = true;
                }

                $map[$identifier] = $limitationsValues;
                unset($policy->limitations[$identifier]);
            }
        }

        if (!$hasStateGroup) {
            return;
        }

        if ($allWildCard) {
            $policy->limitations[Limitation::STATE] = '*';

            return;
        }

        if ($someWildCard) {
            $fullMap = $this->getGroupMap();
            foreach ($map as $identifier => $limitationsValues) {
                if ($limitationsValues === '*') {
                    $map[$identifier] = $fullMap[$identifier];
                }
            }
        }

        $policy->limitations[Limitation::STATE] = [];
        foreach ($map as $limitationValues) {
            $policy->limitations[Limitation::STATE] = array_merge(
                $policy->limitations[Limitation::STATE],
                $limitationValues
            );
        }
    }

    /**
     * Query for groups identifiers and id's.
     *
     * @param array<int>|null $limitIds list of limitation-related IDs
     *
     * @return array<string, int[]>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getGroupMap(?array $limitIds = null): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('sg.identifier', 's.id')
            ->from('ezcobj_state', 's')
            ->innerJoin(
                's',
                'ezcobj_state_group',
                'sg',
                's.group_id = sg.id'
            );

        if ($limitIds !== null) {
            $query->where(
                $query->expr()->in(
                    's.id',
                    $query->createPositionalParameter(
                        array_map('intval', $limitIds),
                        ArrayParameterType::INTEGER
                    )
                )
            );
        }

        $groupValues = $query->executeQuery()->fetchAllAssociative();
        $map = [];
        foreach ($groupValues as $groupValue) {
            $map[self::STATE_GROUP . $groupValue['identifier']][] = (int)$groupValue['id'];
        }

        return $map;
    }
}
