<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Imagine\AliasCleaner;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AliasCleanerTest extends TestCase
{
    private AliasCleaner $aliasCleaner;

    private ResolverInterface & MockObject $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->createMock(ResolverInterface::class);
        $this->aliasCleaner = new AliasCleaner($this->resolver);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testRemoveAliases(): void
    {
        $originalPath = 'foo/bar/test.jpg';
        $this->resolver
            ->expects(self::once())
            ->method('remove')
            ->with([$originalPath], []);

        $this->aliasCleaner->removeAliases($originalPath);
    }
}
