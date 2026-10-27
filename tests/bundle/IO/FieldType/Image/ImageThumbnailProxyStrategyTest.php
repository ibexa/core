<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\IO\FieldType\Image;

use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\ThumbnailStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Thumbnail;
use Ibexa\Core\FieldType\Image\ImageThumbnailProxyStrategy;
use Ibexa\Core\FieldType\Image\ImageThumbnailStrategy;
use Ibexa\Core\MVC\Exception\SourceImageNotFoundException;
use Ibexa\Core\Repository\ProxyFactory\ProxyGenerator;
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

    private ImageThumbnailProxyStrategy $strategy;

    private Field $field;

    protected function setUp(): void
    {
        $this->imageThumbnailStrategyMock = $this->createMock(ImageThumbnailStrategy::class);
        $this->contentThumbnailStrategyMock = $this->createMock(ThumbnailStrategy::class);

        $this->strategy = new ImageThumbnailProxyStrategy(
            $this->imageThumbnailStrategyMock,
            new ProxyGenerator(__DIR__ . '/../../../../../var/proxy'),
            $this->contentThumbnailStrategyMock,
        );

        $this->field = new Field([
            'id' => 123,
            'fieldTypeIdentifier' => 'ezimage',
        ]);
    }

    public function testGetThumbnailReturnsImageThumbnail(): void
    {
        $versionInfo = $this->createVersionInfo(new ContentType());

        $this->imageThumbnailStrategyMock
            ->expects(self::once())
            ->method('createThumbnail')
            ->with($this->field, $versionInfo)
            ->willReturn(new Thumbnail(['resource' => '/image.jpg']));

        $this->contentThumbnailStrategyMock
            ->expects(self::never())
            ->method('getThumbnail');

        $thumbnail = $this->strategy->getThumbnail($this->field, $versionInfo);

        self::assertSame('/image.jpg', $thumbnail->resource);
    }

    public function testGetThumbnailFallsBackToContentThumbnailStrategy(): void
    {
        $contentType = new ContentType();
        $versionInfo = $this->createVersionInfo($contentType);

        $this->imageThumbnailStrategyMock
            ->method('createThumbnail')
            ->willThrowException(new SourceImageNotFoundException('image.jpg'));

        $this->contentThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->with($contentType, [], $versionInfo)
            ->willReturn(new Thumbnail(['resource' => '/placeholder.svg']));

        $thumbnail = $this->strategy->getThumbnail($this->field, $versionInfo);

        self::assertSame('/placeholder.svg', $thumbnail->resource);
    }

    public function testGetThumbnailThrowsExceptionIfFallbackThumbnailIsNull(): void
    {
        $previous = new SourceImageNotFoundException('image.jpg');

        $this->imageThumbnailStrategyMock
            ->method('createThumbnail')
            ->willThrowException($previous);

        $this->contentThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->willReturn(null);

        $thumbnail = $this->strategy->getThumbnail($this->field, $this->createVersionInfo(new ContentType()));

        $this->assertProxyInitializationFails($thumbnail, $previous);
    }

    public function testGetThumbnailThrowsExceptionIfVersionInfoIsNull(): void
    {
        $previous = new SourceImageNotFoundException('image.jpg');

        $this->imageThumbnailStrategyMock
            ->method('createThumbnail')
            ->willThrowException($previous);

        $this->contentThumbnailStrategyMock
            ->expects(self::never())
            ->method('getThumbnail');

        $thumbnail = $this->strategy->getThumbnail($this->field);

        $this->assertProxyInitializationFails($thumbnail, $previous);
    }

    private function createVersionInfo(ContentType $contentType): VersionInfo
    {
        return new VersionInfo([
            'contentInfo' => new ContentInfo(['contentType' => $contentType]),
        ]);
    }

    private function assertProxyInitializationFails(
        Thumbnail $thumbnail,
        SourceImageNotFoundException $previous
    ): void {
        self::assertInstanceOf(LazyLoadingInterface::class, $thumbnail);

        try {
            $thumbnail->initializeProxy();
        } catch (RuntimeException $e) {
            self::assertSame(
                sprintf(
                    'Failed to prepare thumbnail for field "123" (field type: "ezimage") using "%s" strategy.',
                    get_class($this->imageThumbnailStrategyMock),
                ),
                $e->getMessage()
            );
            self::assertSame($previous, $e->getPrevious());

            return;
        }

        self::fail(sprintf('Expected %s to be thrown on proxy initialization.', RuntimeException::class));
    }
}
