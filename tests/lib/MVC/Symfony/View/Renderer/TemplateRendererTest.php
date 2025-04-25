<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\View\Renderer;

use Ibexa\Core\MVC\Exception\NoViewTemplateException;
use Ibexa\Core\MVC\Symfony\Event\PreContentViewEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\Renderer\TemplateRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $renderer;

    private Environment & MockObject $templateEngineMock;

    private EventDispatcherInterface & MockObject $eventDispatcherMock;

    protected function setUp(): void
    {
        $this->templateEngineMock = $this->createMock(Environment::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->renderer = new TemplateRenderer(
            $this->templateEngineMock,
            $this->eventDispatcherMock
        );
    }

    public function testRender(): void
    {
        $view = $this->createView();
        $view->setTemplateIdentifier('path/to/template.html.twig');

        $this->eventDispatcherMock
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(PreContentViewEvent::class),
                MVCEvents::PRE_CONTENT_VIEW
            );

        $this->templateEngineMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'path/to/template.html.twig',
                $view->getParameters()
            );

        $this->renderer->render($view);
    }

    public function testRenderNoViewTemplate(): void
    {
        $this->expectException(NoViewTemplateException::class);

        $this->renderer->render($this->createView());
    }

    protected function createView(): ContentView
    {
        return new ContentView();
    }
}
