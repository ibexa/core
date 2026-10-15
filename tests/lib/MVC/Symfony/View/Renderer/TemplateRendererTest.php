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
use Ibexa\Core\MVC\Symfony\View\View;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class TemplateRendererTest extends TestCase
{
    /** @var TemplateRenderer */
    private $renderer;

    /** @var Environment|MockObject */
    private $templateEngineMock;

    /** @var EventDispatcherInterface|MockObject */
    private $eventDispatcherMock;

    protected function setUp(): void
    {
        $this->templateEngineMock = $this->createMock(Environment::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->renderer = new TemplateRenderer(
            $this->templateEngineMock,
            $this->eventDispatcherMock
        );
    }

    public function testRender()
    {
        $view = $this->createView();
        $view->setTemplateIdentifier('path/to/template.html.twig');

        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(PreContentViewEvent::class),
                MVCEvents::PRE_CONTENT_VIEW
            );

        $this->templateEngineMock
            ->expects($this->once())
            ->method('render')
            ->with(
                'path/to/template.html.twig',
                $view->getParameters()
            );

        $this->renderer->render($view);
    }

    public function testRenderNoViewTemplate()
    {
        $this->expectException(NoViewTemplateException::class);

        $this->renderer->render($this->createView());
    }

    /**
     * @return View
     */
    protected function createView()
    {
        $view = new ContentView();

        return $view;
    }
}

class_alias(TemplateRendererTest::class, 'eZ\Publish\Core\MVC\Symfony\View\Tests\Renderer\TemplateRendererTest');
