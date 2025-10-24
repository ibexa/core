<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\ViewControllerListener;
use Ibexa\Contracts\Core\Event\View\PostBuildViewEvent;
use Ibexa\Core\MVC\Symfony\View\BaseView;
use Ibexa\Core\MVC\Symfony\View\Builder\ViewBuilder;
use Ibexa\Core\MVC\Symfony\View\Builder\ViewBuilderRegistry;
use Ibexa\Core\MVC\Symfony\View\Configurator;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\Event\FilterViewBuilderParametersEvent;
use Ibexa\Core\MVC\Symfony\View\ViewEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewControllerListenerTest extends TestCase
{
    /** @var ControllerResolver|MockObject */
    private $controllerResolver;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var ViewControllerListener */
    private $controllerListener;

    /** @var ControllerEvent */
    private $event;

    /** @var Request */
    private $request;

    /** @var ViewBuilderRegistry|MockObject */
    private $viewBuilderRegistry;

    /** @var Configurator|MockObject */
    private $viewConfigurator;

    /** @var ViewBuilder|MockObject */
    private $viewBuilderMock;

    /** @var EventDispatcherInterface|MockObject */
    private $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerResolver = $this->createMock(ControllerResolverInterface::class);
        $this->viewBuilderRegistry = $this->createMock(ViewBuilderRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controllerListener = new ViewControllerListener(
            $this->controllerResolver,
            $this->viewBuilderRegistry,
            $this->eventDispatcher,
            $this->logger
        );

        $this->request = new Request();
        $this->event = $this->createEvent();

        $this->viewBuilderMock = $this->createMock(ViewBuilder::class);
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [KernelEvents::CONTROLLER => ['getController', 10]],
            $this->controllerListener::getSubscribedEvents()
        );
    }

    public function testGetControllerNoBuilder()
    {
        $initialController = 'Foo::bar';
        $this->request->attributes->set('_controller', $initialController);

        $this->viewBuilderRegistry
            ->expects(self::once())
            ->method('getFromRegistry')
            ->with('Foo::bar')
            ->willReturn(null);

        $this->controllerListener->getController($this->event);
    }

    public function testGetControllerWithClosure()
    {
        $initialController = static function () {};
        $this->request->attributes->set('_controller', $initialController);

        $this->viewBuilderRegistry
            ->expects(self::once())
            ->method('getFromRegistry')
            ->with($initialController)
            ->willReturn(null);

        $this->controllerListener->getController($this->event);
    }

    public function testGetControllerMatchedView()
    {
        $contentId = 12;
        $locationId = 123;
        $viewType = 'full';

        $templateIdentifier = 'FooBundle:full:template.twig.html';
        $customController = 'FooBundle::bar';

        $this->request->attributes->add(
            [
                '_controller' => 'ibexa_content::viewAction',
                'contentId' => $contentId,
                'locationId' => $locationId,
                'viewType' => $viewType,
            ]
        );

        $this->viewBuilderRegistry
            ->expects(self::once())
            ->method('getFromRegistry')
            ->will(self::returnValue($this->viewBuilderMock));

        $viewObject = new ContentView($templateIdentifier);
        $viewObject->setControllerReference(new ControllerReference($customController));

        $this->viewBuilderMock
            ->expects(self::once())
            ->method('buildView')
            ->will(self::returnValue($viewObject));

        $this->controllerResolver
            ->expects(self::once())
            ->method('getController')
            ->will(self::returnValue(static function () {}));

        $this->controllerListener->getController($this->event);
        self::assertEquals($customController, $this->request->attributes->get('_controller'));

        $expectedView = new ContentView();
        $expectedView->setTemplateIdentifier($templateIdentifier);
        $expectedView->setControllerReference(new ControllerReference($customController));

        self::assertEquals($expectedView, $this->request->attributes->get('view'));
    }

    public function testGetControllerEmitsProperEvents(): void
    {
        $viewObject = new class() extends BaseView {};

        $this->viewBuilderRegistry
            ->expects(self::once())
            ->method('getFromRegistry')
            ->willReturn($this->viewBuilderMock);

        $this->viewBuilderMock
            ->expects(self::once())
            ->method('buildView')
            ->willReturn($viewObject);

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(FilterViewBuilderParametersEvent::class),
                    self::identicalTo(ViewEvents::FILTER_BUILDER_PARAMETERS),
                ],
                [
                    self::isInstanceOf(PostBuildViewEvent::class),
                    self::isNull(),
                ]
            )
            ->willReturnArgument(0);

        $this->controllerListener->getController($this->event);
    }

    /**
     * @return ControllerEvent
     */
    protected function createEvent()
    {
        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            static function () {},
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
