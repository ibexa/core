<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\FilterInterface;
use Ibexa\Bundle\Core\Imagine\Filter\Loader\SwirlFilterLoader;
use Imagine\Image\ImageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SwirlFilterLoaderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $filter;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\SwirlFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = $this->createMock(FilterInterface::class);
        $this->loader = new SwirlFilterLoader($this->filter);
    }

    public function testLoadNoOption(): void
    {
        $image = self::createStub(ImageInterface::class);
        $this->filter
            ->expects(self::never())
            ->method('setOption');

        $this->filter
            ->expects(self::once())
            ->method('apply')
            ->with($image)
            ->will(self::returnValue($image));

        self::assertSame($image, $this->loader->load($image));
    }

    #[DataProvider('loadWithOptionProvider')]
    public function testLoadWithOption($degrees): void
    {
        $image = self::createStub(ImageInterface::class);
        $this->filter
            ->expects(self::once())
            ->method('setOption')
            ->with('degrees', $degrees);

        $this->filter
            ->expects(self::once())
            ->method('apply')
            ->with($image)
            ->will(self::returnValue($image));

        self::assertSame($image, $this->loader->load($image, [$degrees]));
    }

    /**
     * @return array<mixed>
     */
    public static function loadWithOptionProvider(): array
    {
        return [
            [10],
            [60],
            [60.34],
            [180.123],
        ];
    }
}
