<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleWidthFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScaleWidthFilterLoaderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private MockObject $innerLoader;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleWidthFilterLoader */
    private ScaleWidthFilterLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScaleWidthFilterLoader();
        $this->loader->setInnerLoader($this->innerLoader);
    }

    public function testLoadFail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader->load($this->createMock(ImageInterface::class, []));
    }

    public function testLoad(): void
    {
        $width = 123;
        $image = $this->createMock(ImageInterface::class);
        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, self::equalTo(['widen' => $width]))
            ->will(self::returnValue($image));

        self::assertSame($image, $this->loader->load($image, [$width]));
    }
}
