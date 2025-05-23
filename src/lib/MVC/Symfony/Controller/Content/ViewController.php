<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use DateTime;
use Exception;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Controller\Controller;
use Ibexa\Core\MVC\Symfony\Event\APIContentExceptionEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * This controller provides the content view feature.
 *
 * @since 6.0.0 All methods except `view()` are deprecated and will be removed in the future.
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
     * @param \Ibexa\Core\MVC\Symfony\View\ContentView $view
     *
     * @return \Ibexa\Core\MVC\Symfony\View\ContentView
     */
    public function viewAction(ContentView $view)
    {
        return $view;
    }

    /**
     * Embed a content.
     * Behaves mostly like viewAction(), but with specific content load permission handling.
     *
     * @param \Ibexa\Core\MVC\Symfony\View\ContentView $view
     *
     * @return \Ibexa\Core\MVC\Symfony\View\ContentView
     */
    public function embedAction(ContentView $view)
    {
        return $view;
    }

    /**
     * Build the response so that depending on settings it's cacheable.
     *
     * @param string|null $etag
     * @param \DateTime|null $lastModified
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse($etag = null, DateTime $lastModified = null)
    {
        $request = $this->getRequest();
        $response = new Response();
        if ($this->getParameter('content.view_cache') === true) {
            $response->setPublic();
            if ($etag !== null) {
                $response->setEtag($etag);
            }

            if ($this->getParameter('content.ttl_cache') === true) {
                $response->setSharedMaxAge(
                    $this->getParameter('content.default_ttl')
                );
            }

            // Make the response vary against X-User-Context-Hash header ensures that an HTTP
            // reverse proxy caches the different possible variations of the
            // response as it can depend on user role for instance.
            if ($request->headers->has('X-User-Context-Hash')) {
                $response->setVary('X-User-Context-Hash');
            }

            if ($lastModified != null) {
                $response->setLastModified($lastModified);
            }
        }

        return $response;
    }

    protected function handleViewException(Response $response, $params, Exception $e, $viewType, $contentId = null, $locationId = null)
    {
        $event = new APIContentExceptionEvent(
            $e,
            [
                'contentId' => $contentId,
                'locationId' => $locationId,
                'viewType' => $viewType,
            ]
        );
        $this->getEventDispatcher()->dispatch($event, MVCEvents::API_CONTENT_EXCEPTION);
        if ($event->hasContentView()) {
            $response->setContent(
                $this->viewManager->renderContentView(
                    $event->getContentView(),
                    $params
                )
            );

            return $response;
        }

        throw $e;
    }

    /**
     * Creates the content to be returned when viewing a Location.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param string $viewType
     * @param bool $layout
     * @param array $params
     *
     * @return string
     */
    protected function renderLocation(Location $location, $viewType, $layout = false, array $params = [])
    {
        return $this->viewManager->renderLocation($location, $viewType, $params + ['no_layout' => !$layout]);
    }

    /**
     * Creates the content to be returned when viewing a Content.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     * @param string $viewType
     * @param bool $layout
     * @param array $params
     *
     * @return string
     */
    protected function renderContent(Content $content, $viewType, $layout = false, array $params = [])
    {
        return $this->viewManager->renderContent($content, $viewType, $params + ['no_layout' => !$layout]);
    }

    /**
     * Performs the access checks.
     */
    protected function performAccessChecks()
    {
        if (!$this->isGranted(new AuthorizationAttribute('content', 'read'))) {
            throw new AccessDeniedException();
        }
    }
}
