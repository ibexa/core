<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View\ParametersInjector;

use Ibexa\Core\MVC\Symfony\View\Event\FilterViewParametersEvent;
use Ibexa\Core\MVC\Symfony\View\ViewEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Injects the 'no_layout' boolean based on the value of the 'layout' attribute.
 */
class NoLayout implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ViewEvents::FILTER_VIEW_PARAMETERS => 'injectCustomParameters'];
    }

    public function injectCustomParameters(FilterViewParametersEvent $event): void
    {
        $parameters = $event->getBuilderParameters();

        $event->getParameterBag()->set(
            'no_layout',
            isset($parameters['layout']) ? !(bool) $parameters['layout'] : false
        );
    }
}
