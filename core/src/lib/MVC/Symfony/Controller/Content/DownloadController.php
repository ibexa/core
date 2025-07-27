<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller\Content;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\MVC\Symfony\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
    public function downloadBinaryFileByIdAction(Request $request, int $contentId, int $fieldId): BinaryStreamResponse
    {
        $content = $this->contentService->loadContent($contentId);
        try {
            $field = $this->findFieldInContent($fieldId, $content);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundException('File', $fieldId);
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
        if ($request->query->has('version')) {
            $version = (int) $request->query->get('version');
            if ($version <= 0) {
                throw new NotFoundException('File', $filename);
            }
            $content = $this->contentService->loadContent($contentId, null, $version);
        } else {
            $content = $this->contentService->loadContent($contentId);
        }

        if ($content->contentInfo->isTrashed()) {
            throw new NotFoundException('File', $filename);
        }

        $field = $this->translationHelper->getTranslatedField(
            $content,
            $fieldIdentifier,
            $request->query->has('inLanguage') ? $request->query->get('inLanguage') : null
        );
        if (!$field instanceof Field) {
            throw new InvalidArgumentException(
                '$fieldIdentifier',
                "'{$fieldIdentifier}' field not present on content #{$content->contentInfo->id} '{$content->contentInfo->name}'"
            );
        }

        $response = new BinaryStreamResponse($this->ioService->loadBinaryFile($field->value->id), $this->ioService);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $field->value->fileName,
            bin2hex(random_bytes(8))
        );

        return $response;
    }
}
