<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScalePercentFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScalePercentFilterLoader
 */
final class ScalePercentFilterLoaderTest extends TestCase
{
    private LoaderInterface & MockObject $innerLoader;

    private ScalePercentFilterLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScalePercentFilterLoader();
        $this->loader->setInnerLoader($this->innerLoader);
    }

    /**
     * @dataProvider loadInvalidProvider
     *
     * @param array<mixed> $options
     */
    public function testLoadInvalidOptions(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader->load($this->createMock(ImageInterface::class), $options);
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

    public function testLoad(): void
    {
        $widthPercent = 40;
        $heightPercent = 125;
        $origWidth = 770;
        $origHeight = 377;
        $expectedWidth = ($origWidth * $widthPercent) / 100;
        $expectedHeight = ($origHeight * $heightPercent) / 100;

        $box = new Box($origWidth, $origHeight);
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects(self::once())
            ->method('getSize')
            ->willReturn($box);

        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, self::equalTo(['size' => [$expectedWidth, $expectedHeight]]))
            ->willReturn($image);

        self::assertSame($image, $this->loader->load($image, [$widthPercent, $heightPercent]));
    }
}
