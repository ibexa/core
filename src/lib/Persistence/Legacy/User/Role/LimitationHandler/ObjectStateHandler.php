<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\User\Role\LimitationHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;
use Ibexa\Core\Persistence\Legacy\User\Role\LimitationHandler;

/**
 * Limitation Handler.
 *
 * Takes care of Converting a Policy limitation from Legacy value to spi value accepted by API.
 */
class ObjectStateHandler extends LimitationHandler
{
    public const STATE_GROUP = 'StateGroup_';

    /**
     * Translate API STATE limitation to Legacy StateGroup_<identifier> limitations.
     */
    public function toLegacy(Policy $policy): void
    {
        if ($policy->limitations !== '*' && isset($policy->limitations[Limitation::STATE])) {
            if ($policy->limitations[Limitation::STATE] === '*') {
                $map = array_fill_keys(array_keys($this->getGroupMap()), '*');
            } else {
                $map = $this->getGroupMap($policy->limitations[Limitation::STATE]);
            }
            $policy->limitations += $map;
            unset($policy->limitations[Limitation::STATE]);
        }
    }

    /**
     * Translate Legacy StateGroup_<identifier> limitations to API STATE limitation.
     */
    public function toSPI(Policy $policy): void
    {
        if ($policy->limitations === '*' || empty($policy->limitations)) {
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
     */
    protected function getGroupMap(array $limitIds = null): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('sg.identifier', 's.id')
            ->from(Gateway::OBJECT_STATE_TABLE, 's')
            ->innerJoin(
                's',
                Gateway::OBJECT_STATE_GROUP_TABLE,
                'sg',
                's.group_id = sg.id'
            );

        if ($limitIds !== null) {
            $query->where(
                $query->expr()->in(
                    's.id',
                    $query->createPositionalParameter(
                        array_map('intval', $limitIds),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        }

        $statement = $query->executeQuery();

        $map = [];
        $groupValues = $statement->fetchAll(FetchMode::ASSOCIATIVE);
        foreach ($groupValues as $groupValue) {
            $map[self::STATE_GROUP . $groupValue['identifier']][] = (int)$groupValue['id'];
        }

        return $map;
    }
}
