<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\CropFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\Loader\CropFilterLoader
 */
final class CropFilterLoaderTest extends TestCase
{
    private LoaderInterface & MockObject $innerLoader;

    private CropFilterLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new CropFilterLoader();
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
            [[123, 456]],
            [[123, 456, 789]],
        ];
    }

    public function testLoad(): void
    {
        $width = 123;
        $height = 789;
        $offsetX = 100;
        $offsetY = 200;

        $image = $this->createMock(ImageInterface::class);
        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, ['size' => [$width, $height], 'start' => [$offsetX, $offsetY]])
            ->willReturn($image);

        self::assertSame($image, $this->loader->load($image, [$width, $height, $offsetX, $offsetY]));
    }
}
