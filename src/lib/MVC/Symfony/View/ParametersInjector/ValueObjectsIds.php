<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View\ParametersInjector;

use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Injects the ID of the view's value objects as view parameters.
 *
 * Required for backward compatibility with custom view controllers that used arguments such as locationId or contentId.
 */
class ValueObjectsIds implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [View\ViewEvents::FILTER_VIEW_PARAMETERS => 'injectValueObjectsIds'];
    }

    public function injectValueObjectsIds(View\Event\FilterViewParametersEvent $event)
    {
        $view = $event->getView();
        $parameterBag = $event->getParameterBag();

        if ($view instanceof View\LocationValueView) {
            if (($location = $view->getLocation()) instanceof Location) {
                $parameterBag->set('locationId', $location->id);
            }
        }
        if ($view instanceof View\ContentValueView) {
            $parameterBag->set('contentId', $view->getContent()->id);
        }
    }
}
