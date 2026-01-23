<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\FilterInterface;
use Ibexa\Bundle\Core\Imagine\Filter\Loader\SwirlFilterLoader;
use Imagine\Image\ImageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SwirlFilterLoaderTest extends TestCase
{
    /** @var MockObject */
    private $filter;

    /** @var SwirlFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = $this->createMock(FilterInterface::class);
        $this->loader = new SwirlFilterLoader($this->filter);
    }

    public function testLoadNoOption()
    {
        $image = $this->createMock(ImageInterface::class);
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

    /**
     * @dataProvider loadWithOptionProvider
     */
    public function testLoadWithOption($degrees)
    {
        $image = $this->createMock(ImageInterface::class);
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

    public function loadWithOptionProvider()
    {
        return [
            [10],
            [60],
            [60.34],
            [180.123],
        ];
    }
}
