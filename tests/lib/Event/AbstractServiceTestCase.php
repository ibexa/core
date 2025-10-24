<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class AbstractServiceTestCase extends TestCase
{
    public function getEventDispatcher(
        string $beforeEventName,
        string $eventName
    ): TraceableEventDispatcher {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener($beforeEventName, static function (BeforeEvent $event) {});
        $eventDispatcher->addListener($eventName, static function (AfterEvent $event) {});

        return new TraceableEventDispatcher(
            $eventDispatcher,
            new Stopwatch()
        );
    }

    /**
     * @param array<array{event: string, priority: int}> $listeners
     *
     * @return array<array{0: string, 1: int}>
     */
    public function getListenersStack(array $listeners): array
    {
        $stack = [];

        foreach ($listeners as $listener) {
            $stack[] = [$listener['event'], $listener['priority']];
        }

        return $stack;
    }
}
