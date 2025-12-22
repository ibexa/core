<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\IO\FieldType\Image;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\FieldType\Image\ImageThumbnailProxyStrategy;
use Ibexa\Core\FieldType\Image\ImageThumbnailStrategy;
use Ibexa\Core\Repository\ProxyFactory\ProxyGenerator;
use Ibexa\Core\Repository\ProxyFactory\ProxyGeneratorInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ImageThumbnailProxyStrategyTest extends TestCase
{
    /** @var \Ibexa\Core\FieldType\Image\ImageThumbnailStrategy&\PHPUnit\Framework\MockObject\MockObject */
    private ImageThumbnailStrategy $imageThumbnailStrategyMock;

    private ProxyGeneratorInterface $proxyGeneratorMock;

    private ImageThumbnailProxyStrategy $strategy;

    protected function setUp(): void
    {
        $this->imageThumbnailStrategyMock = $this->createMock(ImageThumbnailStrategy::class);
        $this->proxyGeneratorMock = new ProxyGenerator(__DIR__ . '/../../../../../var/proxy');

        $this->strategy = new ImageThumbnailProxyStrategy(
            $this->imageThumbnailStrategyMock,
            $this->proxyGeneratorMock,
        );
    }

    public function testGetThumbnailThrowsExceptionIfWrappedObjectIsNull(): void
    {
        $field = $this->createMock(Field::class);
        $field->method('getId')->willReturn(123);
        $field->method('getFieldTypeIdentifier')->willReturn('ezimage');

        $versionInfo = $this->createMock(VersionInfo::class);

        $this->imageThumbnailStrategyMock
            ->expects(self::once())
            ->method('getThumbnail')
            ->with($field, $versionInfo)
            ->willReturn(null);

        $thumbnail = $this->strategy->getThumbnail($field, $versionInfo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to prepare thumbnail for field type "123" (ID: ezimage) using');

        $thumbnail->getMimeType();
    }
}
