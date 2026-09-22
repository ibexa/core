<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as RepositoryNotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\MVC\Symfony\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class DownloadController extends Controller
{
    private ContentService $contentService;

    private IOServiceInterface $ioService;

    private TranslationHelper $translationHelper;

    public function __construct(
        ContainerInterface $container,
        ContentService $contentService,
        IOServiceInterface $ioService,
        TranslationHelper $translationHelper
    ) {
        parent::__construct($container);

        $this->contentService = $contentService;
        $this->ioService = $ioService;
        $this->translationHelper = $translationHelper;
    }

    /**
     * Download binary file identified by field ID.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the field $fieldId can't be found, or the translation can't be found.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If the content is trashed, or can't be found.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions.
     */
    public function downloadBinaryFileByIdAction(
        Request $request,
        int $contentId,
        int $fieldId,
        ?string $filename = null
    ): BinaryStreamResponse {
        if ($filename === null) {
            trigger_deprecation(
                'ibexa/core',
                '5.0',
                'The "ibexa.content.download.field_id" route (/content/download/{contentId}/{fieldId}) is deprecated'
                . ' and will be removed in 6.0.'
                . ' Use the "ibexa.content.download.field_id.filename" route'
                . ' (/content/download/{contentId}/{fieldId}/{filename}) instead.'
            );
        }

        $versionNo = $request->query->has('version') ? $request->query->getInt('version') : null;
        $language = $request->query->has('inLanguage') ? $request->query->get('inLanguage') : null;

        try {
            $content = $this->contentService->loadContent(
                $contentId,
                $language !== null ? [$language] : null,
                $versionNo,
            );
            $field = $this->findFieldInContent($fieldId, $content);
        } catch (RepositoryNotFoundException | InvalidArgumentException $e) {
            throw $this->createFileNotFoundException($e);
        }

        if ($filename !== null && $field->value->fileName !== $filename) {
            throw $this->createFileNotFoundException();
        }

        return $this->downloadBinaryFileAction($contentId, $field->fieldDefIdentifier, $field->value->fileName, $request);
    }

    /**
     * Finds the field with id $fieldId in $content.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the field $fieldId can't be found, or the translation can't be found.
     */
    protected function findFieldInContent(int $fieldId, Content $content): Field
    {
        foreach ($content->getFields() as $field) {
            if ($field->getId() === $fieldId) {
                return $field;
            }
        }

        throw new InvalidArgumentException(
            '$fieldId',
            "Field with id $fieldId not found in Content with id {$content->id}"
        );
    }

    /**
     * Download binary file identified by field identifier.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the field can't be found, or the translation can't be found.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If the content is trashed, or can't be found.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions.
     */
    public function downloadBinaryFileAction(int $contentId, string $fieldIdentifier, string $filename, Request $request): BinaryStreamResponse
    {
        try {
            if ($request->query->has('version')) {
                $version = (int) $request->query->get('version');
                if ($version <= 0) {
                    throw $this->createFileNotFoundException();
                }
                $content = $this->contentService->loadContent($contentId, null, $version);
            } else {
                $content = $this->contentService->loadContent($contentId);
            }
        } catch (RepositoryNotFoundException $e) {
            throw $this->createFileNotFoundException($e);
        }

        if ($content->contentInfo->isTrashed()) {
            throw $this->createFileNotFoundException();
        }

        $field = $this->translationHelper->getTranslatedField(
            $content,
            $fieldIdentifier,
            $request->query->has('inLanguage') ? $request->query->get('inLanguage') : null
        );
        if (!$field instanceof Field) {
            throw $this->createFileNotFoundException();
        }

        if ($field->value->fileName !== $filename) {
            throw $this->createFileNotFoundException();
        }

        $response = new BinaryStreamResponse($this->ioService->loadBinaryFile($field->value->id), $this->ioService);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $field->value->fileName,
            bin2hex(random_bytes(8))
        );

        return $response;
    }

    private function createFileNotFoundException(?Throwable $previous = null): NotFoundHttpException
    {
        return new NotFoundHttpException('File not found', $previous);
    }
}
