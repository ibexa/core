<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Ibexa\Core\Helper\PreviewLocationProvider;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\View\CustomLocationControllerChecker;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PreviewController
{
    use LoggerAwareTrait;

    public const PREVIEW_PARAMETER_NAME = 'isPreview';
    public const CONTENT_VIEW_ROUTE = 'ibexa.content.view';

    /** @var ContentService */
    private $contentService;

    /** @var LocationService */
    private $locationService;

    /** @var PreviewLocationProvider */
    private $locationProvider;

    /** @var HttpKernelInterface */
    private $kernel;

    /** @var ContentPreviewHelper */
    private $previewHelper;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var CustomLocationControllerChecker */
    private $controllerChecker;

    private bool $debugMode;

    public function __construct(
        ContentService $contentService,
        LocationService $locationService,
        HttpKernelInterface $kernel,
        ContentPreviewHelper $previewHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        PreviewLocationProvider $locationProvider,
        CustomLocationControllerChecker $controllerChecker,
        bool $debugMode = false,
        ?LoggerInterface $logger = null
    ) {
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->kernel = $kernel;
        $this->previewHelper = $previewHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->locationProvider = $locationProvider;
        $this->controllerChecker = $controllerChecker;
        $this->debugMode = $debugMode;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws NotImplementedException If Content is missing location as this is not supported in current version
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function previewContentAction(
        Request $request,
        int $contentId,
        int $versionNo,
        string $language,
        ?string $siteAccessName = null,
        ?int $locationId = null
    ): Response {
        $this->previewHelper->setPreviewActive(true);

        try {
            $content = $this->contentService->loadContent($contentId, [$language], $versionNo);
            $location = $locationId !== null
                ? $this->locationService->loadLocation($locationId)
                : $this->locationProvider->loadMainLocationByContent($content);

            if (!$location instanceof Location) {
                throw new NotImplementedException('Preview for content without Locations');
            }

            $this->previewHelper->setPreviewedContent($content);
            $this->previewHelper->setPreviewedLocation($location);
        } catch (UnauthorizedException $e) {
            throw new AccessDeniedException();
        }

        if (!$this->authorizationChecker->isGranted(new AuthorizationAttribute('content', 'versionread', ['valueObject' => $content]))) {
            throw new AccessDeniedException();
        }

        $siteAccess = $this->previewHelper->getOriginalSiteAccess();
        // Only switch if $siteAccessName is set and different from original
        if ($siteAccessName !== null && $siteAccessName !== $siteAccess->name) {
            $siteAccess = $this->previewHelper->changeConfigScope($siteAccessName);
        }

        try {
            $viewType = $request->query->get('viewType', ViewManagerInterface::VIEW_TYPE_FULL);
            $response = $this->kernel->handle(
                $this->getForwardRequest($location, $content, $siteAccess, $request, $language, $viewType),
                HttpKernelInterface::SUB_REQUEST,
                false
            );
        } catch (APINotFoundException $e) {
            $message = sprintf('Location (%s) not found or not available in requested language (%s)', $location->id, $language);
            $this->logger->warning(
                sprintf('%s %s', $message, 'when loading the preview page'),
                ['exception' => $e]
            );
            if ($this->debugMode) {
                throw new BadStateException('Preview page', $message, $e);
            }

            return new Response($message);
        } catch (Exception $e) {
            return $this->buildResponseForGenericPreviewError($location, $content, $e);
        }
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->setPrivate();

        $this->previewHelper->restoreConfigScope();
        $this->previewHelper->setPreviewActive(false);

        return $response;
    }

    /**
     * Returns the Request object that will be forwarded to the kernel for previewing the content.
     */
    protected function getForwardRequest(
        Location $location,
        Content $content,
        SiteAccess $previewSiteAccess,
        Request $request,
        string $language,
        string $viewType = ViewManagerInterface::VIEW_TYPE_FULL
    ): Request {
        $forwardRequestParameters = [
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            // specify a route for RouteReference generator
            '_route' => UrlAliasGenerator::INTERNAL_CONTENT_VIEW_ROUTE,
            '_route_params' => [
                'contentId' => $content->id,
                'locationId' => $location->id,
            ],
            'location' => $location,
            'content' => $content,
            'viewType' => $viewType,
            'layout' => true,
            'params' => [
                'content' => $content,
                'location' => $location,
                self::PREVIEW_PARAMETER_NAME => true,
                'language' => $language,
            ],
            'siteaccess' => $previewSiteAccess,
            'semanticPathinfo' => $request->attributes->get('semanticPathinfo'),
        ];

        if ($this->controllerChecker->usesCustomController($content, $location)) {
            $forwardRequestParameters = [
                '_controller' => 'ibexa_content::viewAction',
                '_route' => self::CONTENT_VIEW_ROUTE,
            ] + $forwardRequestParameters;
        }

        return $request->duplicate(
            null,
            null,
            $forwardRequestParameters
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function buildResponseForGenericPreviewError(
        Location $location,
        Content $content,
        Exception $e
    ): Response {
        $message = '';
        try {
            if ($location->isDraft() && $this->controllerChecker->usesCustomController($content, $location)) {
                $message = <<<EOF
                    <p>The view that rendered this location draft uses a custom controller, and resulted in a fatal error.</p>
                    <p>Location View is deprecated, as it causes issues with preview, such as an empty location id when previewing the first version of a content.</p>
                    EOF;
            }
        } catch (Exception $innerException) {
            $message = 'An exception occurred when handling page preview exception';
            $this->logger->warning(
                'Unable to check if location uses a custom controller when loading the preview page',
                ['exception' => $innerException]
            );
        }

        $this->logger->warning('Unable to load the preview page', ['exception' => $e]);

        $message .= <<<EOF
<p>Unable to load the preview page</p>
<p>See logs for more information</p>
EOF;

        if ($this->debugMode) {
            throw new BadStateException('Preview page', $message, $e);
        }

        return new Response($message);
    }
}
