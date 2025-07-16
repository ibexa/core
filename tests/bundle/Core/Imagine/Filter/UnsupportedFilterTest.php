<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter;

use Ibexa\Bundle\Core\Imagine\Filter\UnsupportedFilter;
use Imagine\Exception\NotSupportedException;
use Imagine\Image\ImageInterface;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\UnsupportedFilter
 */
final class UnsupportedFilterTest extends AbstractFilterTest
{
    public function testLoad(): void
    {
        $filter = new UnsupportedFilter();

        $this->expectException(NotSupportedException::class);
        $filter->apply($this->createMock(ImageInterface::class));
    }
}
