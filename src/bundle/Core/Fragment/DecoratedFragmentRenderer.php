<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Fragment;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\HttpKernel\Fragment\RoutableFragmentRenderer;

class DecoratedFragmentRenderer implements FragmentRendererInterface
{
    private FragmentRendererInterface $innerRenderer;

    private SiteAccessSerializerInterface $siteAccessSerializer;

    private SiteAccessServiceInterface $siteAccessService;

    public function __construct(
        FragmentRendererInterface $innerRenderer,
        SiteAccessSerializerInterface $siteAccessSerializer,
        SiteAccessServiceInterface $siteAccessService
    ) {
        $this->innerRenderer = $innerRenderer;
        $this->siteAccessSerializer = $siteAccessSerializer;
        $this->siteAccessService = $siteAccessService;
    }

    public function setFragmentPath(string $path): void
    {
        if (!$this->innerRenderer instanceof RoutableFragmentRenderer) {
            return;
        }

        $matcher = $this->siteAccessService->getCurrent()?->matcher;
        if ($matcher instanceof SiteAccess\URILexer) {
            $path = $matcher->analyseLink($path);
        }

        $this->innerRenderer->setFragmentPath($path);
    }

    /**
     * Renders a URI and returns the Response content.
     *
     * @param array<string, mixed> $options An array of options
     */
    public function render(string|ControllerReference $uri, Request $request, array $options = []): Response
    {
        if ($uri instanceof ControllerReference && $request->attributes->has('siteaccess')) {
            // Serialize a SiteAccess to get it back after.
            // @see \Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener
            $siteAccess = $request->attributes->get('siteaccess');
            $this->siteAccessSerializer->serializeSiteAccessAsControllerAttributes($siteAccess, $uri);
        }

        return $this->innerRenderer->render($uri, $request, $options);
    }

    /**
     * Gets the name of the strategy.
     */
    public function getName(): string
    {
        return $this->innerRenderer->getName();
    }
}
