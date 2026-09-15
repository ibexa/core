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
    /** @var \PHPUnit\Framework\MockObject\Stub&\Ibexa\Bundle\Core\Imagine\Filter\FilterInterface */
    private \PHPUnit\Framework\MockObject\Stub $filter;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\ReduceNoiseFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = $this->createStub(FilterInterface::class);
        $this->loader = new ReduceNoiseFilterLoader($this->filter);
    }

    public function testLoadInvalidDriver()
    {
        $this->expectException(NotSupportedException::class);

        $this->loader->load($this->createStub(ImageInterface::class));
    }
}
