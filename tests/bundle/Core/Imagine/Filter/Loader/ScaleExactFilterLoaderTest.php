<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleExactFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScaleExactFilterLoaderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $innerLoader;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleExactFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScaleExactFilterLoader();
        $this->loader->setInnerLoader($this->innerLoader);
    }

    #[DataProvider('loadInvalidProvider')]
    public function testLoadInvalidOptions(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader->load(self::createStub(ImageInterface::class), $options);
    }

    /**
     * @return array<mixed>
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
        $image = self::createStub(ImageInterface::class);
        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, ['size' => $options])
            ->will(self::returnValue($image));

        self::assertSame($image, $this->loader->load($image, $options));
    }
}
