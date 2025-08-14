<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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

/**
 * @covers \Ibexa\Bundle\Core\Imagine\PlaceholderProvider\GenericProvider
 */
final class GenericProviderTest extends TestCase
{
    /**
     * @dataProvider getPlaceholderDataProvider
     *
     * @param array<string, mixed> $options
     */
    public function testGetPlaceholder(ImageValue $value, string $expectedText, array $options = []): void
    {
        $font = $this->createMock(AbstractFont::class);

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->expects(self::atLeastOnce())
            ->method('font')
            ->willReturnCallback(function ($fontpath, $fontsize, ColorInterface $foreground) use ($options, $font) {
                $this->assertEquals($options['fontpath'], $fontpath);
                $this->assertEquals($options['fontsize'], $fontsize);
                $this->assertColorEquals($options['foreground'], $foreground);

                return $font;
            });

        $font
            ->expects(self::atLeastOnce())
            ->method('box')
            ->willReturn($this->createMock(BoxInterface::class));

        $image = $this->createMock(ImageInterface::class);

        $imagine
            ->expects(self::atLeastOnce())
            ->method('create')
            ->willReturnCallback(function (BoxInterface $size, ColorInterface $background) use ($value, $options, $image) {
                $this->assertSizeEquals([$value->width, $value->height], $size);
                $this->assertColorEquals($options['background'], $background);

                return $image;
            });

        $drawer = $this->createMock(DrawerInterface::class);
        $image
            ->expects(self::atLeastOnce())
            ->method('draw')
            ->willReturn($drawer);

        $drawer
            ->expects(self::atLeastOnce())
            ->method('text')
            ->with($expectedText, $font);

        $provider = new GenericProvider($imagine);
        $provider->getPlaceholder($value, $options);
    }

    /**
     * @return array<array{\Ibexa\Core\FieldType\Image\Value, string, array<string, mixed>}>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function getPlaceholderDataProvider(): array
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

    /**
     * @param array{int|null, int|null} $expected width, height
     */
    private function assertSizeEquals(array $expected, BoxInterface $actual): void
    {
        self::assertEquals($expected[0], $actual->getWidth());
        self::assertEquals($expected[1], $actual->getHeight());
    }

    private function assertColorEquals(string $expected, ColorInterface $actual): void
    {
        self::assertEquals(strtolower($expected), strtolower((string)$actual));
    }
}
