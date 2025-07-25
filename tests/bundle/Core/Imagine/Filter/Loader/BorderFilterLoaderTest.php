<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\BorderFilterLoader;
use Imagine\Draw\DrawerInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\Loader\BorderFilterLoader
 */
final class BorderFilterLoaderTest extends TestCase
{
    /**
     * @dataProvider loadInvalidProvider
     *
     * @param array<mixed> $options
     */
    public function testLoadInvalidOptions(array $options): void
    {
        $loader = new BorderFilterLoader();

        $this->expectException(InvalidArgumentException::class);
        $loader->load($this->createMock(ImageInterface::class), $options);
    }

    /**
     * @return array<array{array<mixed>}>
     */
    public static function loadInvalidProvider(): array
    {
        return [
            [[]],
            [[123]],
            [['foo' => 'bar']],
        ];
    }

    public function testLoadDefaultColor(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $options = [10, 10];

        $palette = $this->createMock(PaletteInterface::class);
        $image
            ->expects(self::once())
            ->method('palette')
            ->willReturn($palette);
        $palette
            ->expects(self::once())
            ->method('color')
            ->with(BorderFilterLoader::DEFAULT_BORDER_COLOR)
            ->willReturn($this->createMock(ColorInterface::class));

        $box = $this->createMock(BoxInterface::class);
        $image
            ->expects(self::once())
            ->method('getSize')
            ->willReturn($box);
        $box
            ->expects(self::atLeastOnce())
            ->method('getWidth')
            ->willReturn(100);
        $box
            ->expects(self::any())
            ->method('getHeight')
            ->willReturn(100);

        $drawer = $this->createMock(DrawerInterface::class);
        $image
            ->expects(self::once())
            ->method('draw')
            ->willReturn($drawer);
        $drawer
            ->expects(self::atLeastOnce())
            ->method('line')
            ->willReturn($drawer);

        $loader = new BorderFilterLoader();
        self::assertSame($image, $loader->load($image, $options));
    }

    /**
     * @dataProvider loadProvider
     */
    public function testLoad(int $thickX, int $thickY, string $color): void
    {
        $image = $this->createMock(ImageInterface::class);
        $options = [$thickX, $thickY, $color];

        $palette = $this->createMock(PaletteInterface::class);
        $image
            ->expects(self::once())
            ->method('palette')
            ->willReturn($palette);
        $palette
            ->expects(self::once())
            ->method('color')
            ->with($color)
            ->willReturn($this->createMock(ColorInterface::class));

        $box = $this->createMock(BoxInterface::class);
        $image
            ->expects(self::once())
            ->method('getSize')
            ->willReturn($box);
        $box
            ->expects(self::atLeastOnce())
            ->method('getWidth')
            ->willReturn(1000);
        $box
            ->expects(self::atLeastOnce())
            ->method('getHeight')
            ->willReturn(1000);

        $drawer = $this->createMock(DrawerInterface::class);
        $image
            ->expects(self::once())
            ->method('draw')
            ->willReturn($drawer);
        $drawer
            ->expects(self::atLeastOnce())
            ->method('line')
            ->willReturn($drawer);

        $loader = new BorderFilterLoader();
        self::assertSame($image, $loader->load($image, $options));
    }

    /**
     * @return array<array{int, int, string}>
     */
    public static function loadProvider(): array
    {
        return [
            [10, 10, '#fff'],
            [5, 5, '#5dcb4f'],
            [50, 50, '#fa1629'],
        ];
    }
}
