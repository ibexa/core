<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\ImageAsset;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\ThumbnailStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Ibexa\Core\FieldType\ImageAsset\ImageAssetThumbnailStrategy;
use Ibexa\Core\FieldType\ImageAsset\Value;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\ImageAsset\ImageAssetThumbnailStrategy
 */
final class ImageAssetThumbnailStrategyTest extends TestCase
{
    private const ASSET_CONTENT_ID = 42;

    /**
     * @dataProvider provideAssetLoadingExceptions
     */
    public function testGetThumbnailReturnsNullWhenAssetCannotBeLoaded(Exception $exception): void
    {
        $contentService = $this->createMock(ContentService::class);
        $contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with(self::ASSET_CONTENT_ID)
            ->willThrowException($exception);

        $thumbnailStrategy = $this->createMock(ThumbnailStrategy::class);
        $thumbnailStrategy
            ->expects(self::never())
            ->method('getThumbnail');

        $strategy = new ImageAssetThumbnailStrategy('ezimageasset', $thumbnailStrategy, $contentService);

        self::assertNull(
            $strategy->getThumbnail(new Field(['value' => new Value(self::ASSET_CONTENT_ID)]))
        );
    }

    /**
     * @return iterable<string, array{Exception}>
     */
    public static function provideAssetLoadingExceptions(): iterable
    {
        yield 'asset not found' => [
            new NotFoundException('Content', self::ASSET_CONTENT_ID),
        ];

        yield 'asset not readable by the current user' => [
            new UnauthorizedException('content', 'read', ['contentId' => self::ASSET_CONTENT_ID]),
        ];
    }
}
