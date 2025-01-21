<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Fragment;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer as BaseRenderer;
use Symfony\Component\HttpKernel\Fragment\RoutableFragmentRenderer;

class InlineFragmentRenderer extends BaseRenderer implements SiteAccessAware, FragmentRendererInterface
{
    protected FragmentRendererInterface $innerRenderer;

    private ?SiteAccess $siteAccess = null;

    private SiteAccessSerializerInterface $siteAccessSerializer;

    public function __construct(
        FragmentRendererInterface $innerRenderer,
        SiteAccessSerializerInterface $siteAccessSerializer
    ) {
        $this->innerRenderer = $innerRenderer;
        $this->siteAccessSerializer = $siteAccessSerializer;
    }

    public function setFragmentPath($path): void
    {
        if ($this->innerRenderer instanceof RoutableFragmentRenderer) {
            $this->innerRenderer->setFragmentPath($path);
        }
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(string|ControllerReference $uri, Request $request, array $options = []): Response
    {
        if ($uri instanceof ControllerReference) {
            if ($request->attributes->has('siteaccess')) {
                /** @var \Ibexa\Core\MVC\Symfony\SiteAccess $siteAccess */
                $siteAccess = $request->attributes->get('siteaccess');
                $this->siteAccessSerializer->serializeSiteAccessAsControllerAttributes($siteAccess, $uri);
            }
            if ($request->attributes->has('semanticPathinfo')) {
                $uri->attributes['semanticPathinfo'] = $request->attributes->get('semanticPathinfo');
            }
            if ($request->attributes->has('viewParametersString')) {
                $uri->attributes['viewParametersString'] = $request->attributes->get('viewParametersString');
            }
        }

        return $this->innerRenderer->render($uri, $request, $options);
    }

    public function getName(): string
    {
        return $this->innerRenderer->getName();
    }
}
