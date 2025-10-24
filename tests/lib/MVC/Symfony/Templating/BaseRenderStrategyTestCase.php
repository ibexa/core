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
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Tests\Core\Search\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

abstract class BaseRenderStrategyTestCase extends TestCase
{
    /**
     * @phpstan-param class-string<BaseRenderStrategy> $typeClass
     *
     * @param FragmentRendererInterface[] $fragmentRenderers
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
            ) {}

            public function getName(): string
            {
                return $this->name;
            }

            /**
             * @param array<string, mixed> $options
             */
            public function render(
                string | ControllerReference $uri,
                Request $request,
                array $options = []
            ): Response {
                return new Response($this->rendered ?? $this->name . '_rendered');
            }
        };
    }

    public function createLocation(
        APIContent $content,
        int $id
    ): APILocation {
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
}
