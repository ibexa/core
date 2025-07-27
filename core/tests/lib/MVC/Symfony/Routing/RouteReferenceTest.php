<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Core\MVC\Symfony\Routing\RouteReference;
use PHPUnit\Framework\TestCase;

class RouteReferenceTest extends TestCase
{
    public function testConstruct()
    {
        $route = 'my_route';
        $params = ['foo' => 'bar', 'some' => 'thing'];
        $reference = new RouteReference($route, $params);
        self::assertSame($route, $reference->getRoute());
        self::assertSame($params, $reference->getParams());
    }

    public function testGetSetRoute()
    {
        $initialRoute = 'foo';
        $newRoute = 'bar';

        $reference = new RouteReference($initialRoute);
        self::assertSame($initialRoute, $reference->getRoute());
        $reference->setRoute($newRoute);
        self::assertSame($newRoute, $reference->getRoute());
    }

    public function testGetSetParams()
    {
        $reference = new RouteReference('foo');
        self::assertSame([], $reference->getParams());

        $reference->set('foo', 'bar');
        self::assertSame('bar', $reference->get('foo'));
        $obj = new \stdClass();
        $reference->set('object', $obj);
        self::assertSame($obj, $reference->get('object'));
        $reference->set('bool', true);
        self::assertTrue($reference->get('bool'));
        self::assertSame(
            ['foo' => 'bar', 'object' => $obj, 'bool' => true],
            $reference->getParams()
        );

        $defaultValue = 'http://www.phoenix-rises.fm';
        self::assertSame($defaultValue, $reference->get('url', $defaultValue));
    }

    public function testRemoveParam()
    {
        $reference = new RouteReference('foo');
        $reference->set('foo', 'bar');
        self::assertTrue($reference->has('foo'));
        self::assertSame('bar', $reference->get('foo'));

        $reference->remove('foo');
        self::assertFalse($reference->has('foo'));
    }
}
