<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Core\MVC\Symfony\Event\RouteReferenceGenerationEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\Generator\RouteReferenceGenerator;
use Ibexa\Core\MVC\Symfony\Routing\RouteReference;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RouteReferenceGeneratorTest extends TestCase
{
    /** @var MockObject */
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testGenerateNullResource()
    {
        $currentRouteName = 'my_route';
        $currentRouteParams = ['foo' => 'bar'];

        $request = new Request();
        $request->attributes->set('_route', $currentRouteName);
        $request->attributes->set('_route_params', $currentRouteParams);
        $requestStack = new RequestStack([$request]);

        $event = new RouteReferenceGenerationEvent(new RouteReference($currentRouteName, $currentRouteParams), $request);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($event), MVCEvents::ROUTE_REFERENCE_GENERATION);

        $generator = new RouteReferenceGenerator($this->dispatcher);
        $generator->setRequestStack($requestStack);
        $reference = $generator->generate();
        self::assertInstanceOf(RouteReference::class, $reference);
        self::assertSame($currentRouteName, $reference->getRoute());
        self::assertSame($currentRouteParams, $reference->getParams());
    }

    public function testGenerateNullResourceAndPassedParams()
    {
        $currentRouteName = 'my_route';
        $currentRouteParams = ['foo' => 'bar'];
        $passedParams = ['hello' => 'world', 'isIt' => true];
        $expectedParams = $passedParams + $currentRouteParams;

        $request = new Request();
        $request->attributes->set('_route', $currentRouteName);
        $request->attributes->set('_route_params', $currentRouteParams);
        $requestStack = new RequestStack([$request]);

        $event = new RouteReferenceGenerationEvent(new RouteReference($currentRouteName, $expectedParams), $request);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($event), MVCEvents::ROUTE_REFERENCE_GENERATION);

        $generator = new RouteReferenceGenerator($this->dispatcher);
        $generator->setRequestStack($requestStack);
        $reference = $generator->generate(null, $passedParams);
        self::assertInstanceOf(RouteReference::class, $reference);
        self::assertSame($currentRouteName, $reference->getRoute());
        self::assertSame($expectedParams, $reference->getParams());
    }

    /**
     * @dataProvider generateGenerator
     */
    public function testGenerate(
        $resource,
        array $params
    ) {
        $currentRouteName = 'my_route';
        $currentRouteParams = ['foo' => 'bar'];

        $request = new Request();
        $request->attributes->set('_route', $currentRouteName);
        $request->attributes->set('_route_params', $currentRouteParams);
        $requestStack = new RequestStack([$request]);

        $event = new RouteReferenceGenerationEvent(new RouteReference($resource, $params), $request);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($event), MVCEvents::ROUTE_REFERENCE_GENERATION);

        $generator = new RouteReferenceGenerator($this->dispatcher);
        $generator->setRequestStack($requestStack);
        $reference = $generator->generate($resource, $params);
        self::assertInstanceOf(RouteReference::class, $reference);
        self::assertSame($resource, $reference->getRoute());
        self::assertSame($params, $reference->getParams());
    }

    public function testGenerateNullResourceWithoutRoute()
    {
        $currentRouteName = 'my_route';
        $currentRouteParams = ['foo' => 'bar'];

        $request = new Request();
        $requestStack = new RequestStack([$request]);

        $event = new RouteReferenceGenerationEvent(new RouteReference(null, []), $request);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($event), MVCEvents::ROUTE_REFERENCE_GENERATION);

        $generator = new RouteReferenceGenerator($this->dispatcher);
        $generator->setRequestStack($requestStack);
        $reference = $generator->generate();
        self::assertInstanceOf(RouteReference::class, $reference);
    }

    public function generateGenerator()
    {
        return [
            ['my_route', ['hello' => 'world', 'isIt' => true]],
            ['foobar', ['foo' => 'bar', 'object' => new \stdClass()]],
            [new Location(), ['switchLanguage' => 'fre-FR']],
            [new Location(), []],
        ];
    }
}
