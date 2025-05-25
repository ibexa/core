<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\MVC\Templating;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

abstract class BaseRenderStrategy implements RenderStrategy
{
    /** @phpstan-var array<string, \Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface> */
    protected array $fragmentRenderers;

    protected string $defaultRenderer;

    protected SiteAccess $siteAccess;

    protected RequestStack $requestStack;

    /**
     * @param \Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface[] $fragmentRenderers
     */
    public function __construct(
        array $fragmentRenderers,
        string $defaultRenderer,
        SiteAccess $siteAccess,
        RequestStack $requestStack
    ) {
        $this->fragmentRenderers = [];
        foreach ($fragmentRenderers as $fragmentRenderer) {
            $this->fragmentRenderers[$fragmentRenderer->getName()] = $fragmentRenderer;
        }

        $this->defaultRenderer = $defaultRenderer;
        $this->siteAccess = $siteAccess;
        $this->requestStack = $requestStack;
    }

    protected function getFragmentRenderer(string $name): FragmentRendererInterface
    {
        if (empty($this->fragmentRenderers[$name])) {
            throw new InvalidArgumentException('method', sprintf(
                'Fragment renderer "%s" does not exist.',
                $name
            ));
        }

        return $this->fragmentRenderers[$name];
    }
}
