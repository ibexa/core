<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleHeightDownOnlyFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleHeightDownOnlyFilterLoader
 */
final class ScaleHeightDownOnlyFilterLoaderTest extends TestCase
{
    private LoaderInterface & MockObject $innerLoader;

    private ScaleHeightDownOnlyFilterLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScaleHeightDownOnlyFilterLoader();
        $this->loader->setInnerLoader($this->innerLoader);
    }

    public function testLoadInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader->load($this->createMock(ImageInterface::class), []);
    }

    public function testLoad(): void
    {
        $height = 123;
        $image = $this->createMock(ImageInterface::class);
        $this->innerLoader
            ->expects(self::once())
            ->method('load')
            ->with($image, self::equalTo(['size' => [null, $height], 'mode' => 'inset']))
            ->willReturn($image);

        self::assertSame($image, $this->loader->load($image, [$height]));
    }
}
