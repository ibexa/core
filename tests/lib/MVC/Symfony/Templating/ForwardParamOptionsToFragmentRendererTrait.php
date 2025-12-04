<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\MVC\Templating\RenderStrategy;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

trait ForwardParamOptionsToFragmentRendererTrait
{
    /**
     * @param \Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface|\PHPUnit\Framework\MockObject\MockObject $fragmentRendererMock
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject|\PHPUnit\Framework\MockObject\MockObject $valueObjectMock
     * @param class-string<RenderStrategy> $renderStrategyClass
     */
    public function forwardParamOptionsToFragmentRenderer(
        object $fragmentRendererMock,
        object $valueObjectMock,
        string $renderStrategyClass
    ): void {
        $params = [
            'param1' => 'value1',
            'param2' => 'value2',
        ];

        $fragmentRendererMock
            ->method('getName')
            ->willReturn('fragment_render_mock');
        $fragmentRendererMock->expects(self::once())
            ->method('render')
            ->with(
                self::callback(static function ($controllerReference) use ($params) {
                    if (!$controllerReference instanceof ControllerReference) {
                        return false;
                    }

                    return $controllerReference->attributes['params'] === $params;
                }),
                self::anything(),
            )
            ->willReturn(new Response('fragment_render_mock_rendered'));

        $renderContentStrategy = self::createRenderStrategy(
            $renderStrategyClass,
            [
                $fragmentRendererMock,
            ],
        );

        /** @var \Ibexa\Contracts\Core\Repository\Values\ValueObject&\PHPUnit\Framework\MockObject\MockObject $valueObjectMock */
        TestCase::assertTrue($renderContentStrategy->supports($valueObjectMock));

        TestCase::assertSame(
            'fragment_render_mock_rendered',
            $renderContentStrategy->render($valueObjectMock, new RenderOptions([
                'method' => 'fragment_render_mock',
                'viewType' => 'awesome',
                'params' => $params,
            ]))
        );
    }
}
