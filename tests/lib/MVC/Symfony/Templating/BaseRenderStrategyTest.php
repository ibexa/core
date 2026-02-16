<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\MVC\Templating\RenderStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Tests\Core\Search\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

abstract class BaseRenderStrategyTest extends TestCase
{
    public function createRenderStrategy(
        string $typeClass,
        array $fragmentRenderers,
        string $defaultMethod = 'inline',
        string $siteAccessName = 'default',
        ?Request $request = null
    ): RenderStrategy {
        $siteAccess = new SiteAccess($siteAccessName);

        $requestStack = new RequestStack();
        $requestStack->push($request ?? new Request());

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
        return new class($name, $rendered) implements FragmentRendererInterface {
            /** @var string */
            private $name;

            private ?string $rendered;

            public function __construct(
                string $name,
                ?string $rendered
            ) {
                $this->name = $name;
                $this->rendered = $rendered;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function render(
                $uri,
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
     * @param \Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface&\PHPUnit\Framework\MockObject\MockObject $fragmentRendererMock
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject&\PHPUnit\Framework\MockObject\MockObject $valueObjectMock
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

        /** @var \Ibexa\Contracts\Core\Repository\Values\ValueObject&\PHPUnit\Framework\MockObject\MockObject $valueObjectMock */
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

class_alias(BaseRenderStrategyTest::class, 'eZ\Publish\Core\MVC\Symfony\Templating\Tests\BaseRenderStrategyTest');
