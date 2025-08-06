<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\GrayscaleFilterLoader;
use Imagine\Effects\EffectsInterface;
use Imagine\Image\ImageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\Loader\GrayscaleFilterLoader
 */
final class GrayscaleFilterLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $effects = $this->createMock(EffectsInterface::class);
        $image
            ->expects(self::once())
            ->method('effects')
            ->willReturn($effects);
        $effects
            ->expects(self::once())
            ->method('grayscale')
            ->willReturn($effects);

        $loader = new GrayscaleFilterLoader();
        self::assertSame($image, $loader->load($image));
    }
}
