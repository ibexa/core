<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\MVC\Symfony\Controller\Controller;
use Ibexa\Core\MVC\Symfony\Routing\Generator\RouteReferenceGenerator;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class DownloadRedirectionController extends Controller
{
    private ContentService $contentService;

    private RouterInterface $router;

    private RouteReferenceGenerator $routeReferenceGenerator;

    public function __construct(
        ContainerInterface $container,
        ContentService $contentService,
        RouterInterface $router,
        RouteReferenceGenerator $routeReferenceGenerator
    ) {
        parent::__construct($container);

        $this->contentService = $contentService;
        $this->router = $router;
        $this->routeReferenceGenerator = $routeReferenceGenerator;
    }

    /**
     * Used by the REST API to reference downloadable files.
     * It redirects (permanently) to the standard ez_content_download route, based on the language of the field
     * passed as an argument, using the language switcher.
     */
    public function redirectToContentDownloadAction(
        int $contentId,
        int $fieldId,
        Request $request
    ): RedirectResponse {
        $content = $this->contentService->loadContent($contentId);
        $field = $this->findFieldInContent($fieldId, $content);

        $params = [
            'content' => $content,
            'fieldIdentifier' => $field->fieldDefIdentifier,
            'language' => $field->languageCode,
        ];

        if ($request->query->has('version')) {
            $params['version'] = $request->query->get('version');
        }

        $downloadRouteRef = $this->routeReferenceGenerator->generate(
            'ibexa.content.download',
            $params
        );

        $downloadUrl = $this->router->generate(
            $downloadRouteRef->getRoute(),
            $downloadRouteRef->getParams()
        );

        return new RedirectResponse($downloadUrl, Response::HTTP_FOUND);
    }

    /**
     * Finds the field with id $fieldId in $content.
     */
    protected function findFieldInContent(
        int $fieldId,
        Content $content
    ): Field {
        foreach ($content->getFields() as $field) {
            if ($field->id == $fieldId) {
                return $field;
            }
        }
        throw new InvalidArgumentException("Could not find any Field with ID $fieldId in Content item with ID {$content->id}");
    }
}
