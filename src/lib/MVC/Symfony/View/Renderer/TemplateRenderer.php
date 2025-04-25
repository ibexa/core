<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View\Renderer;

use Closure;
use Ibexa\Core\MVC\Exception\NoViewTemplateException;
use Ibexa\Core\MVC\Symfony\Event\PreContentViewEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\View\Renderer;
use Ibexa\Core\MVC\Symfony\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class TemplateRenderer implements Renderer
{
    protected Environment $templateEngine;

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(Environment $templateEngine, EventDispatcherInterface $eventDispatcher)
    {
        $this->templateEngine = $templateEngine;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\View\View $view
     *
     * @throws \Ibexa\Core\MVC\Exception\NoViewTemplateException
     *
     * @return string
     */
    public function render(View $view)
    {
        $this->eventDispatcher->dispatch(new PreContentViewEvent($view), MVCEvents::PRE_CONTENT_VIEW);

        $templateIdentifier = $view->getTemplateIdentifier();
        if ($templateIdentifier instanceof Closure) {
            return $templateIdentifier($view->getParameters());
        }

        if ($view->getTemplateIdentifier() === null) {
            throw new NoViewTemplateException($view);
        }

        return $this->templateEngine->render(
            $view->getTemplateIdentifier(),
            $view->getParameters()
        );
    }
}
