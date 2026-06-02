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
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController
 */
final class DownloadControllerTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\ContentService&\PHPUnit\Framework\MockObject\MockObject */
    private ContentService $contentService;

    /** @var \Ibexa\Core\IO\IOServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private IOServiceInterface $ioService;

    /** @var \Ibexa\Core\Helper\TranslationHelper&\PHPUnit\Framework\MockObject\MockObject */
    private TranslationHelper $translationHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentService = $this->createMock(ContentService::class);
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->translationHelper = $this->createMock(TranslationHelper::class);
    }

    public function testDownloadBinaryFileActionReturnsBinaryResponseWhenFilenameMatches(): void
    {
        $content = $this->createContent();
        $field = $content->getField('file', 'eng-GB');
        self::assertInstanceOf(Field::class, $field);

        $request = new Request(['inLanguage' => 'eng-GB']);
        $binaryFile = new BinaryFile([
            'id' => 'binary-file-id',
            'mtime' => new DateTime(),
            'size' => 123,
            'uri' => 'binary-file-uri',
        ]);

        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with(42)
            ->willReturn($content);
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslatedField')
            ->with($content, 'file', 'eng-GB')
            ->willReturn($field);
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with('binary-file-id')
            ->willReturn($binaryFile);

        $response = $this->createController()->downloadBinaryFileAction(42, 'file', 'Test-file.pdf', $request);

        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('Test-file.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function testDownloadBinaryFileActionReturnsNotFoundWhenFilenameDoesNotMatch(): void
    {
        $content = $this->createContent();
        $field = $content->getField('file', 'eng-GB');
        self::assertInstanceOf(Field::class, $field);

        $request = new Request(['inLanguage' => 'eng-GB']);

        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with(42)
            ->willReturn($content);
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslatedField')
            ->with($content, 'file', 'eng-GB')
            ->willReturn($field);
        $this->ioService
            ->expects($this->never())
            ->method('loadBinaryFile');

        $this->expectException(NotFoundException::class);
        $this->createController()->downloadBinaryFileAction(42, 'file', 'SomeRandomText.txt', $request);
    }

    private function createController(): DownloadController
    {
        return new DownloadController(
            $this->contentService,
            $this->ioService,
            $this->translationHelper
        );
    }

    private function createContent(): Content
    {
        return new Content([
            'internalFields' => [
                new Field([
                    'fieldDefIdentifier' => 'file',
                    'languageCode' => 'eng-GB',
                    'value' => new BinaryFileValue([
                        'id' => 'binary-file-id',
                        'fileName' => 'Test-file.pdf',
                    ]),
                ]),
            ],
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 42,
                    'mainLanguageCode' => 'eng-GB',
                    'name' => 'Test content',
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
            ]),
        ]);
    }
}
