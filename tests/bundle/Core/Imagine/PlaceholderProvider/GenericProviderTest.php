<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\PlaceholderProvider;

use Ibexa\Bundle\Core\Imagine\PlaceholderProvider\GenericProvider;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Imagine\Draw\DrawerInterface;
use Imagine\Image\AbstractFont;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use PHPUnit\Framework\TestCase;

class GenericProviderTest extends TestCase
{
    /**
     * @dataProvider getPlaceholderDataProvider
     */
    public function testGetPlaceholder(
        ImageValue $value,
        $expectedText,
        array $options = []
    ) {
        $font = $this->createMock(AbstractFont::class);

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->expects(self::atLeastOnce())
            ->method('font')
            ->willReturnCallback(function (
                $fontpath,
                $fontsize,
                ColorInterface $foreground
            ) use ($options, $font) {
                $this->assertEquals($options['fontpath'], $fontpath);
                $this->assertEquals($options['fontsize'], $fontsize);
                $this->assertColorEquals($options['foreground'], $foreground);

                return $font;
            });

        $font
            ->expects(self::any())
            ->method('box')
            ->willReturn($this->createMock(BoxInterface::class));

        $image = $this->createMock(ImageInterface::class);

        $imagine
            ->expects(self::atLeastOnce())
            ->method('create')
            ->willReturnCallback(function (
                BoxInterface $size,
                ColorInterface $background
            ) use ($value, $options, $image) {
                $this->assertSizeEquals([$value->width, $value->height], $size);
                $this->assertColorEquals($options['background'], $background);

                return $image;
            });

        $drawer = $this->createMock(DrawerInterface::class);
        $image
            ->expects(self::any())
            ->method('draw')
            ->willReturn($drawer);

        $drawer
            ->expects(self::atLeastOnce())
            ->method('text')
            ->with($expectedText, $font);

        $provider = new GenericProvider($imagine);
        $provider->getPlaceholder($value, $options);
    }

    public function getPlaceholderDataProvider()
    {
        return [
            [
                new ImageValue([
                    'id' => 'photo.jpg',
                    'width' => 640,
                    'height' => 480,
                ]),
                "IMAGE PLACEHOLDER 640x480\n(photo.jpg)",
                [
                    'background' => '#00FF00',
                    'foreground' => '#FF0000',
                    'fontsize' => 72,
                    'text' => "IMAGE PLACEHOLDER %width%x%height%\n(%id%)",
                    'fontpath' => '/path/to/font.ttf',
                ],
            ],
        ];
    }

    private function assertSizeEquals(
        array $expected,
        BoxInterface $actual
    ) {
        self::assertEquals($expected[0], $actual->getWidth());
        self::assertEquals($expected[1], $actual->getHeight());
    }

    private function assertColorEquals(
        $expected,
        ColorInterface $actual
    ) {
        self::assertEquals(strtolower($expected), strtolower((string)$actual));
    }
}
