<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\PreContentViewEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class Manager implements ViewManagerInterface
{
    /** @var Environment */
    protected $templateEngine;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Repository */
    protected $repository;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * The base layout template to use when the view is requested to be generated
     * outside of the pagelayout.
     *
     * @var string
     */
    protected $viewBaseLayout;

    /** @var ConfigResolverInterface */
    protected $configResolver;

    /** @var Configurator */
    private $viewConfigurator;

    public function __construct(
        Environment $templateEngine,
        EventDispatcherInterface $eventDispatcher,
        Repository $repository,
        ConfigResolverInterface $configResolver,
        $viewBaseLayout,
        $viewConfigurator,
        ?LoggerInterface $logger = null
    ) {
        $this->templateEngine = $templateEngine;
        $this->eventDispatcher = $eventDispatcher;
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->viewBaseLayout = $viewBaseLayout;
        $this->logger = $logger;
        $this->viewConfigurator = $viewConfigurator;
    }

    /**
     * Renders $content by selecting the right template.
     * $content will be injected in the selected template.
     *
     * @param Content $content
     * @param string $viewType Variation of display for your content. Default is 'full'.
     * @param array $parameters Parameters to pass to the template called to
     *        render the view. By default, it's empty. 'content' entry is
     *        reserved for the Content that is rendered.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function renderContent(
        Content $content,
        $viewType = ViewManagerInterface::VIEW_TYPE_FULL,
        $parameters = []
    ) {
        $view = new ContentView(null, $parameters, $viewType);
        $view->setContent($content);
        if (isset($parameters['location'])) {
            $view->setLocation($parameters['location']);
        }

        $this->viewConfigurator->configure($view);

        if ($view->getTemplateIdentifier() === null) {
            throw new RuntimeException('Unable to find a template for #' . $content->contentInfo->id);
        }

        return $this->renderContentView($view, $parameters);
    }

    /**
     * Renders $location by selecting the right template for $viewType.
     * $content and $location will be injected in the selected template.
     *
     * @param Location $location
     * @param string $viewType Variation of display for your content. Default is 'full'.
     * @param array $parameters Parameters to pass to the template called to
     *        render the view. By default, it's empty. 'location' and 'content'
     *        entries are reserved for the Location (and its Content) that is
     *        viewed.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function renderLocation(
        Location $location,
        $viewType = ViewManagerInterface::VIEW_TYPE_FULL,
        $parameters = []
    ) {
        if (!isset($parameters['location'])) {
            $parameters['location'] = $location;
        }

        if (!isset($parameters['content'])) {
            $parameters['content'] = $this->repository->getContentService()->loadContentByContentInfo(
                $location->contentInfo,
                $this->configResolver->getParameter('languages')
            );
        }

        return $this->renderContent($parameters['content'], $viewType, $parameters);
    }

    /**
     * Renders passed ContentView object via the template engine.
     * If $view's template identifier is a closure, then it is called directly and the result is returned as is.
     *
     * @param View $view
     * @param array $defaultParams
     *
     * @return string
     */
    public function renderContentView(
        View $view,
        array $defaultParams = []
    ) {
        $defaultParams['view_base_layout'] = $this->viewBaseLayout;
        $view->addParameters($defaultParams);
        $this->eventDispatcher->dispatch(new PreContentViewEvent($view), MVCEvents::PRE_CONTENT_VIEW);

        $templateIdentifier = $view->getTemplateIdentifier();
        $params = $view->getParameters();
        if ($templateIdentifier instanceof \Closure) {
            return $templateIdentifier($params);
        }

        return $this->templateEngine->render($templateIdentifier, $params);
    }
}
