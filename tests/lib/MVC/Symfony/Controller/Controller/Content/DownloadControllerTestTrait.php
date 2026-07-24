<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Controller\Controller\Content;

use DateTime;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;

trait DownloadControllerTestTrait
{
    /** @var \Ibexa\Contracts\Core\Repository\ContentService&\PHPUnit\Framework\MockObject\MockObject */
    private ContentService $contentService;

    /** @var \Ibexa\Core\IO\IOServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private IOServiceInterface $ioService;

    /** @var \Ibexa\Core\Helper\TranslationHelper&\PHPUnit\Framework\MockObject\MockObject */
    private TranslationHelper $translationHelper;

    private function initializeServiceMocks(): void
    {
        $this->contentService = $this->createMock(ContentService::class);
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->translationHelper = $this->createMock(TranslationHelper::class);
    }

    private function createController(): DownloadController
    {
        return new DownloadController(
            $this->contentService,
            $this->ioService,
            $this->translationHelper
        );
    }

    private function expectContentLoaded(int $contentId, Content $content): void
    {
        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with($contentId)
            ->willReturn($content);
    }

    private function expectTranslatedField(Content $content, string $fieldIdentifier, ?Field $field): void
    {
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslatedField')
            ->with($content, $fieldIdentifier, 'eng-GB')
            ->willReturn($field);
    }

    private function expectBinaryFileLoaded(BinaryFile $binaryFile): void
    {
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with('binary-file-id')
            ->willReturn($binaryFile);
    }

    private function expectBinaryFileNotLoaded(): void
    {
        $this->ioService
            ->expects(self::never())
            ->method('loadBinaryFile');
    }

    private function createBinaryFile(): BinaryFile
    {
        return new BinaryFile([
            'id' => 'binary-file-id',
            'mtime' => new DateTime(),
            'size' => 123,
            'uri' => 'binary-file-uri',
        ]);
    }

    private function createContent(string $fileName, int $contentId = 42, string $contentName = 'Test content'): Content
    {
        return new Content([
            'internalFields' => [
                new Field([
                    'id' => 7,
                    'fieldDefIdentifier' => 'file',
                    'languageCode' => 'eng-GB',
                    'value' => new BinaryFileValue([
                        'id' => 'binary-file-id',
                        'fileName' => $fileName,
                    ]),
                ]),
            ],
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => $contentId,
                    'mainLanguageCode' => 'eng-GB',
                    'name' => $contentName,
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
            ]),
        ]);
    }
}
