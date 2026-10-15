<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Controller;

use Ibexa\Core\MVC\Symfony\Controller\Controller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Controller\Controller::render
 *
 * @mvc
 */
class ControllerTest extends TestCase
{
    /** @var Controller */
    protected $controller;

    /** @var MockObject */
    protected $templateEngineMock;

    /** @var MockObject */
    protected $containerMock;

    protected function setUp(): void
    {
        $this->templateEngineMock = $this->createMock(EngineInterface::class);
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->controller = $this->getMockForAbstractClass(Controller::class, [$this->containerMock]);
        $this->containerMock
            ->expects(self::any())
            ->method('get')
            ->with('templating')
            ->will(self::returnValue($this->templateEngineMock));
    }

    public function testRender()
    {
        $view = 'some:valid:view.html.twig';
        $params = ['foo' => 'bar', 'truc' => 'muche'];
        $tplResult = "I'm a template result";
        $this->templateEngineMock
            ->expects(self::once())
            ->method('render')
            ->with($view, $params)
            ->will(self::returnValue($tplResult));
        $response = $this->controller->render($view, $params);
        self::assertInstanceOf(Response::class, $response);
        self::assertSame($tplResult, $response->getContent());
    }

    public function testRenderWithResponse()
    {
        $response = new Response();
        $view = 'some:valid:view.html.twig';
        $params = ['foo' => 'bar', 'truc' => 'muche'];
        $tplResult = "I'm a template result";
        $this->templateEngineMock
            ->expects(self::once())
            ->method('render')
            ->with($view, $params)
            ->will(self::returnValue($tplResult));

        self::assertSame($response, $this->controller->render($view, $params, $response));
        self::assertSame($tplResult, $response->getContent());
    }
}
