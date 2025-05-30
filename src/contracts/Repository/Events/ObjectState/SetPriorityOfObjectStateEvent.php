<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ObjectState;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;

final class SetPriorityOfObjectStateEvent extends AfterEvent
{
    private ObjectState $objectState;

    private int $priority;

    public function __construct(
        ObjectState $objectState,
        int $priority
    ) {
        $this->objectState = $objectState;
        $this->priority = $priority;
    }

    public function getObjectState(): ObjectState
    {
        return $this->objectState;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
