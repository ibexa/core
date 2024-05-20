<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Event;

use Ibexa\Core\MVC\Symfony\Event\RouteReferenceGenerationEvent;
use Ibexa\Core\MVC\Symfony\Routing\RouteReference;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RouteReferenceGenerationEventTest extends TestCase
{
    public function testConstruct()
    {
        $routeReference = new RouteReference('foo');
        $request = new Request();
        $event = new RouteReferenceGenerationEvent($routeReference, $request);
        self::assertSame($routeReference, $event->getRouteReference());
        self::assertSame($request, $event->getRequest());
    }

    public function testGetSet()
    {
        $routeReference = new RouteReference('foo');
        $request = new Request();

        $event = new RouteReferenceGenerationEvent($routeReference, $request);
        self::assertSame($routeReference, $event->getRouteReference());
        self::assertSame($request, $event->getRequest());

        $newRouteReference = new RouteReference('bar');
        $event->setRouteReference($newRouteReference);
        self::assertSame($newRouteReference, $event->getRouteReference());
    }
}
