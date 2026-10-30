<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Image;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Variation\Values\ImageVariation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\FieldType\Image\ImageThumbnailStrategy;
use Ibexa\Core\MVC\Exception\SourceImageNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ImageThumbnailStrategyTest extends TestCase
{
    /** @var VariationHandler&Stub */
    private VariationHandler $variationHandler;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    private ImageThumbnailStrategy $strategy;

    protected function setUp(): void
    {
        $this->variationHandler = $this->createStub(VariationHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->strategy = new ImageThumbnailStrategy(
            'ezimage',
            $this->variationHandler,
            'medium',
            $this->logger
        );
    }

    public function testCreateThumbnail(): void
    {
        $this->variationHandler
            ->method('getVariation')
            ->willReturn(new ImageVariation([
                'uri' => '/image_medium.jpg',
                'width' => 200,
                'height' => 100,
                'mimeType' => 'image/jpeg',
            ]));

        $this->logger
            ->expects(self::never())
            ->method('warning');

        $thumbnail = $this->strategy->createThumbnail(new Field());

        self::assertSame('/image_medium.jpg', $thumbnail->resource);
        self::assertSame(200, $thumbnail->width);
        self::assertSame(100, $thumbnail->height);
        self::assertSame('image/jpeg', $thumbnail->mimeType);
    }

    public function testCreateThumbnailLogsAndRethrowsException(): void
    {
        $exception = new SourceImageNotFoundException('image.jpg');

        $this->variationHandler
            ->method('getVariation')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(self::anything(), ['exception' => $exception]);

        $this->expectExceptionObject($exception);

        $this->strategy->createThumbnail(new Field());
    }

    public function testGetThumbnailLogsExceptionAndReturnsNull(): void
    {
        $exception = new SourceImageNotFoundException('image.jpg');

        $this->variationHandler
            ->method('getVariation')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(self::anything(), ['exception' => $exception]);

        self::assertNull($this->strategy->getThumbnail(new Field()));
    }
}
