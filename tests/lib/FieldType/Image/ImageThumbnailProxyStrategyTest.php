<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Image;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\ThumbnailStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Thumbnail;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\Image\ImageThumbnailProxyStrategy;
use Ibexa\Core\FieldType\Image\ImageThumbnailStrategy;
use Ibexa\Core\Repository\ProxyFactory\ProxyGenerator;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\LazyLoadingInterface;
use RuntimeException;

final class ImageThumbnailProxyStrategyTest extends TestCase
{
    /** @var ImageThumbnailStrategy&MockObject */
    private ImageThumbnailStrategy $imageThumbnailStrategyMock;

    /** @var ThumbnailStrategy&MockObject */
    private ThumbnailStrategy $contentThumbnailStrategyMock;

    /** @var ContentService&MockObject */
    private ContentService $contentServiceMock;

    private ImageThumbnailProxyStrategy $strategy;

    private Field $field;

    protected function setUp(): void
    {
        $this->imageThumbnailStrategyMock = $this->createMock(ImageThumbnailStrategy::class);
        $this->contentThumbnailStrategyMock = $this->createMock(ThumbnailStrategy::class);
        $this->contentServiceMock = $this->createMock(ContentService::class);

        $this->strategy = new ImageThumbnailProxyStrategy(
            $this->imageThumbnailStrategyMock,
            new ProxyGenerator(__DIR__ . '/../../../../var/proxy'),
            $this->contentThumbnailStrategyMock,
            $this->contentServiceMock,
        );

        $this->field = new Field([
            'id' => 123,
            'fieldDefIdentifier' => 'image',
            'fieldTypeIdentifier' => 'ezimage',
        ]);
    }

    public function testGetThumbnailReturnsImageThumbnail(): void
    {
        $versionInfo = $this->createVersionInfo(new ContentType());

        $this->imageThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->with($this->field, $versionInfo)
            ->willReturn(new Thumbnail(['resource' => '/image.jpg']));

        $this->contentThumbnailStrategyMock
            ->expects(self::never())
            ->method('getThumbnail');

        $thumbnail = $this->strategy->getThumbnail($this->field, $versionInfo);

        self::assertSame('/image.jpg', $thumbnail->resource);
    }

    public function testGetThumbnailFallsBackToContentThumbnailStrategyWithoutImageFields(): void
    {
        $contentType = new ContentType();
        $versionInfo = $this->createVersionInfo($contentType);
        $nameField = new Field(['id' => 124, 'fieldDefIdentifier' => 'first_name', 'fieldTypeIdentifier' => 'ezstring']);

        $this->expectImageThumbnail($versionInfo, null);

        $this->contentServiceMock
            ->expects(self::once())
            ->method('loadContentByVersionInfo')
            ->with($versionInfo)
            ->willReturn(new Content([
                'contentType' => $contentType,
                'versionInfo' => $versionInfo,
                'internalFields' => [$this->field, $nameField],
            ]));

        $this->contentThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->with($contentType, [$nameField], $versionInfo)
            ->willReturn(new Thumbnail(['resource' => '/placeholder.svg']));

        $thumbnail = $this->strategy->getThumbnail($this->field, $versionInfo);

        self::assertSame('/placeholder.svg', $thumbnail->resource);
    }

    public function testGetThumbnailThrowsExceptionIfFallbackThumbnailIsNull(): void
    {
        $versionInfo = $this->createVersionInfo(new ContentType());

        $this->expectImageThumbnail($versionInfo, null);
        $this->expectContentLoad($versionInfo);

        $this->contentThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->willReturn(null);

        $thumbnail = $this->strategy->getThumbnail($this->field, $versionInfo);

        $this->assertProxyInitializationFails($thumbnail, null);
    }

    public function testGetThumbnailThrowsExceptionWithFallbackExceptionAsPrevious(): void
    {
        $previous = new NotFoundException('ContentType', 1);
        $versionInfo = $this->createVersionInfo(new ContentType());

        $this->expectImageThumbnail($versionInfo, null);
        $this->expectContentLoad($versionInfo);

        $this->contentThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->willThrowException($previous);

        $thumbnail = $this->strategy->getThumbnail($this->field, $versionInfo);

        $this->assertProxyInitializationFails($thumbnail, $previous);
    }

    public function testGetThumbnailThrowsExceptionIfVersionInfoIsNull(): void
    {
        $this->expectImageThumbnail(null, null);

        $this->contentServiceMock
            ->expects(self::never())
            ->method('loadContentByVersionInfo');

        $this->contentThumbnailStrategyMock
            ->expects(self::never())
            ->method('getThumbnail');

        $thumbnail = $this->strategy->getThumbnail($this->field);

        $this->assertProxyInitializationFails($thumbnail, null);
    }

    private function expectImageThumbnail(
        ?VersionInfo $versionInfo,
        ?Thumbnail $thumbnail
    ): void {
        $this->imageThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->with($this->field, $versionInfo)
            ->willReturn($thumbnail);
    }

    private function expectContentLoad(VersionInfo $versionInfo): void
    {
        $this->contentServiceMock
            ->expects(self::once())
            ->method('loadContentByVersionInfo')
            ->with($versionInfo)
            ->willReturn(new Content([
                'contentType' => $versionInfo->getContentInfo()->getContentType(),
                'versionInfo' => $versionInfo,
                'internalFields' => [$this->field],
            ]));
    }

    private function createVersionInfo(ContentType $contentType): VersionInfo
    {
        return new VersionInfo([
            'contentInfo' => new ContentInfo(['contentType' => $contentType]),
        ]);
    }

    private function assertProxyInitializationFails(
        Thumbnail $thumbnail,
        ?Exception $previous
    ): void {
        self::assertInstanceOf(LazyLoadingInterface::class, $thumbnail);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Failed to prepare thumbnail for field "123" (field type: "ezimage") using "%s" strategy.',
            get_class($this->imageThumbnailStrategyMock),
        ));

        try {
            $thumbnail->initializeProxy();
        } catch (RuntimeException $e) {
            self::assertSame($previous, $e->getPrevious());

            throw $e;
        }
    }
}
