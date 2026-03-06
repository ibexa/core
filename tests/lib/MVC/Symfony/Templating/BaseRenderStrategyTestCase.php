<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\MVC\Templating\BaseRenderStrategy;
use Ibexa\Contracts\Core\MVC\Templating\RenderStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Tests\Core\Search\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

abstract class BaseRenderStrategyTestCase extends TestCase
{
    /**
     * @phpstan-param class-string<\Ibexa\Contracts\Core\MVC\Templating\BaseRenderStrategy> $typeClass
     *
     * @param \Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface[] $fragmentRenderers
     */
    public function createRenderStrategy(
        string $typeClass,
        array $fragmentRenderers,
        string $defaultMethod = 'inline',
        string $siteAccessName = 'default',
        ?Request $request = null
    ): RenderStrategy {
        $siteAccess = new SiteAccess($siteAccessName);

        $requestStack = new RequestStack([$request ?? new Request()]);

        return new $typeClass(
            $fragmentRenderers,
            $defaultMethod,
            $siteAccess,
            $requestStack
        );
    }

    public function createFragmentRenderer(
        string $name = 'inline',
        ?string $rendered = null
    ): FragmentRendererInterface {
        return new readonly class($name, $rendered) implements FragmentRendererInterface {
            public function __construct(
                private string $name,
                private ?string $rendered
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            /**
             * @phpstan-param array<string, mixed> $options
             */
            public function render(
                string|ControllerReference $uri,
                Request $request,
                array $options = []
            ): Response {
                return new Response($this->rendered ?? $this->name . '_rendered');
            }
        };
    }

    public function createLocation(APIContent $content, int $id): APILocation
    {
        return new Location([
            'id' => $id,
            'contentInfo' => $content->versionInfo->contentInfo,
            'content' => $content,
        ]);
    }

    public function createContent(int $id): APIContent
    {
        return new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => $id,
                ]),
            ]),
        ]);
    }

    /**
     * @phpstan-param class-string<BaseRenderStrategy> $renderStrategyClass
     */
    public function forwardParamOptionsToFragmentRenderer(
        FragmentRendererInterface & MockObject $fragmentRendererMock,
        ValueObject & MockObject $valueObjectMock,
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
                self::callback(static function ($controllerReference) use ($params): bool {
                    if (!$controllerReference instanceof ControllerReference) {
                        return false;
                    }

                    return $controllerReference->attributes['params'] === $params;
                }),
            )
            ->willReturn(new Response('fragment_render_mock_rendered'));

        $renderContentStrategy = $this->createRenderStrategy(
            $renderStrategyClass,
            [
                $fragmentRendererMock,
            ],
        );

        self::assertTrue($renderContentStrategy->supports($valueObjectMock));

        self::assertSame(
            'fragment_render_mock_rendered',
            $renderContentStrategy->render($valueObjectMock, new RenderOptions([
                'method' => 'fragment_render_mock',
                'viewType' => 'awesome',
                'params' => $params,
            ]))
        );
    }
}
