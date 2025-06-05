<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SafeContentController extends AbstractController
{
    use LoggerAwareTrait;

    protected Repository $repository;

    public function __construct(
        Repository $repository,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function safeViewAction(
        int $contentId,
        string $viewType = 'embed',
        bool $noLayout = true,
        string $fallbackTemplate = '@IbexaCore/default/content/asset_unavailable.html.twig'
    ): Response {
        try {
            $this->repository->getContentService()->loadContent($contentId);
        } catch (NotFoundException|UnauthorizedException $e) {
            $this->logger->warning(
                sprintf('Failed to load content with ID: %d', $contentId),
                ['exception' => $e]
            );

            return $this->render($fallbackTemplate, [
                'asset_id' => $contentId,
            ]);
        }

        return $this->forward('ibexa_content:viewAction', [
            'contentId' => $contentId,
            'viewType' => $viewType,
            'no_layout' => $noLayout,
        ]);
    }
}
