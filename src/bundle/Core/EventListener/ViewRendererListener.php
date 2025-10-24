<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\EventListener;

use Ibexa\Core\MVC\Symfony\View\Renderer;
use Ibexa\Core\MVC\Symfony\View\Renderer as ViewRenderer;
use Ibexa\Core\MVC\Symfony\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewRendererListener implements EventSubscriberInterface
{
    /** @var Renderer */
    private $viewRenderer;

    public function __construct(ViewRenderer $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => 'renderView'];
    }

    public function renderView(ViewEvent $event)
    {
        if (!($view = $event->getControllerResult()) instanceof View) {
            return;
        }

        if (!($response = $view->getResponse()) instanceof Response) {
            $response = new Response();
        }

        $response->setContent($this->viewRenderer->render($view));

        $event->setResponse($response);
    }
}
