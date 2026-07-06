<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\FieldType\BinaryBase;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\MVC\Symfony\FieldType\BinaryBase\ContentDownloadUrlGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \Ibexa\Core\MVC\Symfony\FieldType\BinaryBase\ContentDownloadUrlGenerator
 */
final class ContentDownloadUrlGeneratorTest extends TestCase
{
    private const ROUTE = 'ibexa.content.download.field_id.filename';

    /** @var \Symfony\Component\Routing\RouterInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RouterInterface $router;

    private ContentDownloadUrlGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->createMock(RouterInterface::class);
        $this->generator = new ContentDownloadUrlGenerator($this->router);
    }

    public function testGetRouteReturnsFilenameAwareRoute(): void
    {
        self::assertSame(
            self::ROUTE,
            $this->generator->getRoute($this->createField(), $this->createVersionInfo())
        );
    }

    public function testGetParametersContainFilename(): void
    {
        self::assertSame(
            [
                'contentId' => 42,
                'fieldId' => 7,
                'version' => 2,
                'filename' => 'Test-file.pdf',
            ],
            $this->generator->getParameters($this->createField(), $this->createVersionInfo())
        );
    }

    public function testGetStoragePathForFieldGeneratesFilenameAwareUrl(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with(
                self::ROUTE,
                [
                    'contentId' => 42,
                    'fieldId' => 7,
                    'version' => 2,
                    'filename' => 'Test-file.pdf',
                ]
            )
            ->willReturn('/content/download/42/7/Test-file.pdf?version=2');

        self::assertSame(
            '/content/download/42/7/Test-file.pdf?version=2',
            $this->generator->getStoragePathForField($this->createField(), $this->createVersionInfo())
        );
    }

    private function createField(): Field
    {
        return new Field([
            'id' => 7,
            'value' => new FieldValue([
                'externalData' => [
                    'fileName' => 'Test-file.pdf',
                ],
            ]),
        ]);
    }

    private function createVersionInfo(): VersionInfo
    {
        return new VersionInfo([
            'versionNo' => 2,
            'contentInfo' => new ContentInfo([
                'id' => 42,
            ]),
        ]);
    }
}
