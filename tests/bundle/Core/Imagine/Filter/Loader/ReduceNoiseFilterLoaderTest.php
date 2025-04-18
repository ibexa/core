<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\FilterInterface;
use Ibexa\Bundle\Core\Imagine\Filter\Loader\ReduceNoiseFilterLoader;
use Imagine\Exception\NotSupportedException;
use Imagine\Image\ImageInterface;
use PHPUnit\Framework\TestCase;

class ReduceNoiseFilterLoaderTest extends TestCase
{
    private ReduceNoiseFilterLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $filter = $this->createMock(FilterInterface::class);
        $this->loader = new ReduceNoiseFilterLoader($filter);
    }

    public function testLoadInvalidDriver(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->loader->load($this->createMock(ImageInterface::class));
    }
}
