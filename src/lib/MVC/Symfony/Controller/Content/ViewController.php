<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use Ibexa\Core\MVC\Symfony\Controller\Controller;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This controller provides the content view feature.
 */
class ViewController extends Controller
{
    protected ViewManagerInterface $viewManager;

    public function __construct(
        ContainerInterface $container,
        ViewManagerInterface $viewManager,
    ) {
        parent::__construct($container);

        $this->viewManager = $viewManager;
    }

    /**
     * This is the default view action or a ContentView object.
     *
     * It doesn't do anything by itself: the returned View object is rendered by the ViewRendererListener
     * into an HttpFoundation Response.
     *
     * This action can be selectively replaced by a custom action by means of content_view
     * configuration. Custom actions can add parameters to the view and customize the Response the View will be
     * converted to. They may also bypass the ViewRenderer by returning an HttpFoundation Response.
     *
     * Cache is in both cases handled by the CacheViewResponseListener.
     *
     * @param ContentView $view
     *
     * @return ContentView
     */
    public function viewAction(ContentView $view)
    {
        return $view;
    }

    /**
     * Embed a content.
     * Behaves mostly like viewAction(), but with specific content load permission handling.
     *
     * @param ContentView $view
     *
     * @return ContentView
     */
    public function embedAction(ContentView $view)
    {
        return $view;
    }
}
