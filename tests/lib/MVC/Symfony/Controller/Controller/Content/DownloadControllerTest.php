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
use Ibexa\Core\Base\Exceptions\NotFoundException as BaseNotFoundException;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $binaryFile = $this->createBinaryFile();

        $this->expectContentLoaded(42, $content);
        $this->expectTranslatedField($content, 'file', $field);
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

        $this->expectContentLoaded(42, $content);
        $this->expectTranslatedField($content, 'file', $field);
        $this->expectBinaryFileNotLoaded();

        $this->assertFileNotFound(function () use ($request): void {
            $this->createController()->downloadBinaryFileAction(42, 'file', 'SomeRandomText.txt', $request);
        });
    }

    public function testDownloadBinaryFileActionReturnsNotFoundWhenFieldIdentifierDoesNotMatch(): void
    {
        $content = $this->createContent(393, 'New file');
        $request = new Request(['inLanguage' => 'eng-GB']);

        $this->expectContentLoaded(393, $content);
        $this->expectTranslatedField($content, 'file5', null);
        $this->expectBinaryFileNotLoaded();

        $this->assertFileNotFound(function () use ($request): void {
            $this->createController()->downloadBinaryFileAction(393, 'file5', 'snorelax_snooze.png', $request);
        });
    }

    public function testDownloadBinaryFileActionReturnsNotFoundWhenContentDoesNotExist(): void
    {
        $request = new Request(['inLanguage' => 'eng-GB']);

        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with(393)
            ->willThrowException(new BaseNotFoundException('Content', 393));
        $this->translationHelper
            ->expects($this->never())
            ->method('getTranslatedField');
        $this->expectBinaryFileNotLoaded();

        $this->assertFileNotFound(function () use ($request): void {
            $this->createController()->downloadBinaryFileAction(393, 'file', 'snorelax_snooze.png', $request);
        });
    }

    public function testDownloadBinaryFileByIdActionReturnsNotFoundWhenFieldIdDoesNotMatch(): void
    {
        $content = $this->createContent();
        $request = new Request();

        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with(42, null, null)
            ->willReturn($content);
        $this->translationHelper
            ->expects($this->never())
            ->method('getTranslatedField');
        $this->expectBinaryFileNotLoaded();

        $this->assertFileNotFound(function () use ($request): void {
            $this->createController()->downloadBinaryFileByIdAction($request, 42, 123);
        });
    }

    private function createController(): DownloadController
    {
        return new DownloadController(
            $this->contentService,
            $this->ioService,
            $this->translationHelper
        );
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

    private function expectBinaryFileNotLoaded(): void
    {
        $this->ioService
            ->expects(self::never())
            ->method('loadBinaryFile');
    }

    private function assertFileNotFound(callable $callback): void
    {
        try {
            $callback();
            self::fail(sprintf('Expected %s to be thrown.', NotFoundHttpException::class));
        } catch (NotFoundHttpException $e) {
            self::assertSame('File not found', $e->getMessage());
        }
    }

    private function createContent(int $contentId = 42, string $contentName = 'Test content'): Content
    {
        return new Content([
            'internalFields' => [
                new Field([
                    'id' => 7,
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
                    'id' => $contentId,
                    'mainLanguageCode' => 'eng-GB',
                    'name' => $contentName,
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
            ]),
        ]);
    }
}
