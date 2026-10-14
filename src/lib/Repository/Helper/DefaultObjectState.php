<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Helper;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState;

/**
 * Resolves the Object State new Content gets in a group.
 *
 * @internal
 */
final class DefaultObjectState
{
    /**
     * @param iterable<\Ibexa\Contracts\Core\Persistence\Content\ObjectState> $groupStates all States of one group
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState|null the lowest priority State, null for a group without States
     */
    public static function fromGroupStates(iterable $groupStates): ?ObjectState
    {
        $defaultState = null;
        foreach ($groupStates as $state) {
            if ($defaultState === null || $state->priority < $defaultState->priority) {
                $defaultState = $state;
            }
        }

        return $defaultState;
    }
}
