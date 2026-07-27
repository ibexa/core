<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Controller\Controller\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Base\Exceptions\NotFoundException as BaseNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController
 */
final class DownloadControllerTest extends TestCase
{
    use DownloadControllerTestTrait;

    private const string FILENAME = 'Test-file.pdf';

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeServiceMocks();
    }

    public function testDownloadBinaryFileActionReturnsBinaryResponseWhenFilenameMatches(): void
    {
        $content = $this->createContent(self::FILENAME);
        $field = $content->getField('file', 'eng-GB');
        self::assertInstanceOf(Field::class, $field);

        $request = new Request(['inLanguage' => 'eng-GB']);
        $binaryFile = $this->createBinaryFile();

        $this->expectContentLoaded(42, $content);
        $this->expectTranslatedField($content, 'file', $field);
        $this->expectBinaryFileLoaded($binaryFile);

        $response = $this->createController()->downloadBinaryFileAction(42, 'file', self::FILENAME, $request);

        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString(self::FILENAME, (string) $response->headers->get('Content-Disposition'));
    }

    public function testDownloadBinaryFileActionReturnsNotFoundWhenFilenameDoesNotMatch(): void
    {
        $content = $this->createContent(self::FILENAME);
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
        $content = $this->createContent(self::FILENAME, 393, 'New file');
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
        $content = $this->createContent(self::FILENAME);
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

    private function assertFileNotFound(callable $callback): void
    {
        try {
            $callback();
            self::fail(sprintf('Expected %s to be thrown.', NotFoundHttpException::class));
        } catch (NotFoundHttpException $e) {
            self::assertSame('File not found', $e->getMessage());
        }
    }
}
