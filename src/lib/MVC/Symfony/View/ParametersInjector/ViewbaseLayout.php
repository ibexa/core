<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View\ParametersInjector;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\View\Event\FilterViewParametersEvent;
use Ibexa\Core\MVC\Symfony\View\ViewEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Injects the 'viewBaseLayout' view parameter, set by the container parameter.
 */
class ViewbaseLayout implements EventSubscriberInterface
{
    /** @var string */
    private $viewbaseLayout;

    /** @var ConfigResolverInterface */
    private $configResolver;

    public function __construct(
        $viewbaseLayout,
        ConfigResolverInterface $configResolver
    ) {
        $this->viewbaseLayout = $viewbaseLayout;
        $this->configResolver = $configResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [ViewEvents::FILTER_VIEW_PARAMETERS => 'injectViewbaseLayout'];
    }

    private function getPageLayout(): string
    {
        return $this->configResolver->getParameter('page_layout');
    }

    public function injectViewbaseLayout(FilterViewParametersEvent $event)
    {
        $pageLayout = $this->getPageLayout();

        $event->getParameterBag()->set('view_base_layout', $this->viewbaseLayout);
        $event->getParameterBag()->set('page_layout', $pageLayout);
    }
}
