<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\BorderFilterLoader;
use Imagine\Draw\DrawerInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use PHPUnit\Framework\TestCase;

class BorderFilterLoaderTest extends TestCase
{
    /**
     * @dataProvider loadInvalidProvider
     */
    public function testLoadInvalidOptions(array $options)
    {
        $this->expectException(InvalidArgumentException::class);

        $loader = new BorderFilterLoader();
        $loader->load($this->createMock(ImageInterface::class), $options);
    }

    public function loadInvalidProvider()
    {
        return [
            [[]],
            [[123]],
            [['foo' => 'bar']],
        ];
    }

    public function testLoadDefaultColor()
    {
        $image = $this->createMock(ImageInterface::class);
        $options = [10, 10];

        $palette = $this->createMock(PaletteInterface::class);
        $image
            ->expects(self::once())
            ->method('palette')
            ->will(self::returnValue($palette));
        $palette
            ->expects(self::once())
            ->method('color')
            ->with(BorderFilterLoader::DEFAULT_BORDER_COLOR)
            ->will(self::returnValue($this->createMock(ColorInterface::class)));

        $box = $this->createMock(BoxInterface::class);
        $image
            ->expects(self::once())
            ->method('getSize')
            ->will(self::returnValue($box));
        $box
            ->expects(self::any())
            ->method('getWidth')
            ->will(self::returnValue(100));
        $box
            ->expects(self::any())
            ->method('getHeight')
            ->will(self::returnValue(100));

        $drawer = $this->createMock(DrawerInterface::class);
        $image
            ->expects(self::once())
            ->method('draw')
            ->will(self::returnValue($drawer));
        $drawer
            ->expects(self::any())
            ->method('line')
            ->will(self::returnValue($drawer));

        $loader = new BorderFilterLoader();
        self::assertSame($image, $loader->load($image, $options));
    }

    /**
     * @dataProvider loadProvider
     */
    public function testLoad($thickX, $thickY, $color)
    {
        $image = $this->createMock(ImageInterface::class);
        $options = [$thickX, $thickY, $color];

        $palette = $this->createMock(PaletteInterface::class);
        $image
            ->expects(self::once())
            ->method('palette')
            ->will(self::returnValue($palette));
        $palette
            ->expects(self::once())
            ->method('color')
            ->with($color)
            ->will(self::returnValue($this->createMock(ColorInterface::class)));

        $box = $this->createMock(BoxInterface::class);
        $image
            ->expects(self::once())
            ->method('getSize')
            ->will(self::returnValue($box));
        $box
            ->expects(self::any())
            ->method('getWidth')
            ->will(self::returnValue(1000));
        $box
            ->expects(self::any())
            ->method('getHeight')
            ->will(self::returnValue(1000));

        $drawer = $this->createMock(DrawerInterface::class);
        $image
            ->expects(self::once())
            ->method('draw')
            ->will(self::returnValue($drawer));
        $drawer
            ->expects(self::any())
            ->method('line')
            ->will(self::returnValue($drawer));

        $loader = new BorderFilterLoader();
        self::assertSame($image, $loader->load($image, $options));
    }

    public function loadProvider()
    {
        return [
            [10, 10, '#fff'],
            [5, 5, '#5dcb4f'],
            [50, 50, '#fa1629'],
        ];
    }
}
