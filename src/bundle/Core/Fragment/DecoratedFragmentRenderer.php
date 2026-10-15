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
use Symfony\Component\HttpKernel\Fragment\RoutableFragmentRenderer;

class DecoratedFragmentRenderer implements FragmentRendererInterface, SiteAccessAware
{
    /** @var FragmentRendererInterface */
    private $innerRenderer;

    /** @var SiteAccess */
    private $siteAccess;

    private SiteAccessSerializerInterface $siteAccessSerializer;

    public function __construct(
        FragmentRendererInterface $innerRenderer,
        SiteAccessSerializerInterface $siteAccessSerializer
    ) {
        $this->innerRenderer = $innerRenderer;
        $this->siteAccessSerializer = $siteAccessSerializer;
    }

    /**
     * @param SiteAccess|null $siteAccess
     */
    public function setSiteAccess(?SiteAccess $siteAccess = null)
    {
        $this->siteAccess = $siteAccess;
    }

    public function setFragmentPath($path)
    {
        if (!$this->innerRenderer instanceof RoutableFragmentRenderer) {
            return null;
        }

        if ($this->siteAccess && $this->siteAccess->matcher instanceof SiteAccess\URILexer) {
            $path = $this->siteAccess->matcher->analyseLink($path);
        }

        $this->innerRenderer->setFragmentPath($path);
    }

    /**
     * Renders a URI and returns the Response content.
     *
     * @param string|ControllerReference $uri A URI as a string or a ControllerReference instance
     * @param Request $request A Request instance
     * @param array $options An array of options
     *
     * @return Response A Response instance
     */
    public function render(
        $uri,
        Request $request,
        array $options = []
    ) {
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
     *
     * @return string The strategy name
     */
    public function getName()
    {
        return $this->innerRenderer->getName();
    }
}

class_alias(DecoratedFragmentRenderer::class, 'eZ\Bundle\EzPublishCoreBundle\Fragment\DecoratedFragmentRenderer');
