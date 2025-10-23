<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\Templating\RenderContentStrategy;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class RenderContentStrategyTest extends BaseRenderStrategyTestCase
{
    public function testUnsupportedValueObject(): void
    {
        $renderContentStrategy = $this->createRenderStrategy(
            RenderContentStrategy::class,
            [
                $this->createFragmentRenderer(),
            ]
        );

        $valueObject = new class() extends ValueObject {};
        self::assertFalse($renderContentStrategy->supports($valueObject));

        $this->expectException(InvalidArgumentException::class);
        $renderContentStrategy->render($valueObject, new RenderOptions());
    }

    public function testDefaultFragmentRenderer(): void
    {
        $renderContentStrategy = $this->createRenderStrategy(
            RenderContentStrategy::class,
            [
                $this->createFragmentRenderer('inline'),
            ],
            'inline'
        );

        $contentMock = $this->createMock(Content::class);
        self::assertTrue($renderContentStrategy->supports($contentMock));

        self::assertSame(
            'inline_rendered',
            $renderContentStrategy->render($contentMock, new RenderOptions())
        );
    }

    public function testUnknownFragmentRenderer(): void
    {
        $renderContentStrategy = $this->createRenderStrategy(
            RenderContentStrategy::class,
            [],
        );

        $contentMock = $this->createMock(Content::class);
        self::assertTrue($renderContentStrategy->supports($contentMock));

        $this->expectException(InvalidArgumentException::class);
        $renderContentStrategy->render($contentMock, new RenderOptions());
    }

    public function testMultipleFragmentRenderers(): void
    {
        $renderContentStrategy = $this->createRenderStrategy(
            RenderContentStrategy::class,
            [
                $this->createFragmentRenderer('method_a'),
                $this->createFragmentRenderer('method_b'),
                $this->createFragmentRenderer('method_c'),
            ],
        );

        $contentMock = $this->createMock(Content::class);
        self::assertTrue($renderContentStrategy->supports($contentMock));

        self::assertSame(
            'method_b_rendered',
            $renderContentStrategy->render($contentMock, new RenderOptions([
                'method' => 'method_b',
            ]))
        );
    }

    public function testDuplicatedFragmentRenderers(): void
    {
        $renderContentStrategy = $this->createRenderStrategy(
            RenderContentStrategy::class,
            [
                $this->createFragmentRenderer('method_a', 'decorator service used'),
                $this->createFragmentRenderer('method_a', 'original service used'),
            ],
        );

        $contentMock = $this->createMock(Content::class);
        self::assertTrue($renderContentStrategy->supports($contentMock));

        self::assertSame(
            'decorator service used',
            $renderContentStrategy->render($contentMock, new RenderOptions([
                'method' => 'method_a',
            ]))
        );
    }

    public function testExpectedMethodRenderArgumentsFormat(): void
    {
        $request = new Request();
        $request->headers->set('Surrogate-Capability', 'TEST/1.0');

        $siteAccess = new SiteAccess('some_siteaccess');
        $content = $this->createContent(123);

        $fragmentRendererMock = $this->createMock(FragmentRendererInterface::class);
        $fragmentRendererMock
            ->method('getName')
            ->willReturn('method_b');

        $controllerReferenceCallback = self::callback(function (ControllerReference $controllerReference): bool {
            $this->assertInstanceOf(ControllerReference::class, $controllerReference);
            $this->assertEquals('ibexa_content::viewAction', $controllerReference->controller);
            $this->assertSame([
                'contentId' => 123,
                'viewType' => 'awesome',
            ], $controllerReference->attributes);

            return true;
        });

        $requestCallback = self::callback(function (Request $request) use ($siteAccess, $content): bool {
            $this->assertSame('TEST/1.0', $request->headers->get('Surrogate-Capability'));

            return true;
        });

        $fragmentRendererMock
            ->expects(self::once())
            ->method('render')
            ->with($controllerReferenceCallback, $requestCallback)
            ->willReturn(new Response('some_rendered_content'));

        $renderContentStrategy = $this->createRenderStrategy(
            RenderContentStrategy::class,
            [
                $this->createFragmentRenderer('method_a'),
                $fragmentRendererMock,
                $this->createFragmentRenderer('method_c'),
            ],
            'method_a',
            $siteAccess->name,
            $request
        );

        self::assertSame('some_rendered_content', $renderContentStrategy->render(
            $content,
            new RenderOptions([
                'method' => 'method_b',
                'viewType' => 'awesome',
            ])
        ));
    }
}
