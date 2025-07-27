<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScalePercentFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\TestCase;

class ScalePercentFilterLoaderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $innerLoader;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScalePercentFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScalePercentFilterLoader();
        $this->loader->setInnerLoader($this->innerLoader);
    }

    /**
     * @dataProvider loadInvalidProvider
     */
    public function testLoadInvalidOptions(array $options)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader->load($this->createMock(ImageInterface::class), $options);
    }

    public function loadInvalidProvider()
    {
        return [
            [[]],
            [[123]],
            [['foo' => 'bar']],
        ];
    }

    public function testLoad()
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
            ->will(self::returnValue($box));

        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, self::equalTo(['size' => [$expectedWidth, $expectedHeight]]))
            ->will(self::returnValue($image));

        self::assertSame($image, $this->loader->load($image, [$widthPercent, $heightPercent]));
    }
}
