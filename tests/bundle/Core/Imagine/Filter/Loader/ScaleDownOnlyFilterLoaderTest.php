<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleDownOnlyFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleDownOnlyFilterLoader
 */
final class ScaleDownOnlyFilterLoaderTest extends TestCase
{
    private LoaderInterface & MockObject $innerLoader;

    private ScaleDownOnlyFilterLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScaleDownOnlyFilterLoader();
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
        $options = [123, 456];
        $image = $this->createMock(ImageInterface::class);
        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, self::equalTo(['size' => $options, 'mode' => 'inset']))
            ->willReturn($image);

        self::assertSame($image, $this->loader->load($image, $options));
    }
}
