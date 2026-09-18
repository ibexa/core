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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ReduceNoiseFilterLoaderTest extends TestCase
{
    private FilterInterface&Stub $filter;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\ReduceNoiseFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = self::createStub(FilterInterface::class);
        $this->loader = new ReduceNoiseFilterLoader($this->filter);
    }

    public function testLoadInvalidDriver(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->loader->load(self::createStub(ImageInterface::class));
    }
}
