<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\PathPrefixer;

use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;
use Ibexa\Tests\Core\Search\TestCase;

/**
 * @covers \Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface
 */
abstract class BaseSiteAccessAwarePathPrefixerTest extends TestCase
{
    abstract protected function getPrefixer(): PathPrefixerInterface;

    abstract public function getDataForTestPrefixPath(): iterable;

    abstract public function getDataForTestPrefixDirectoryPath(): iterable;

    abstract public function getDataForTestStripPrefixPath(): iterable;

    public function getDataForTestStripDirectoryPrefix(): iterable
    {
        // treat file names as directories
        yield from $this->getDataForTestStripPrefixPath();
    }

    /**
     * @dataProvider getDataForTestPrefixPath
     */
    public function testPrefixPath(string $expectedPrefixedPath, string $path): void
    {
        self::assertSame(
            $expectedPrefixedPath,
            $this->getPrefixer()->prefixPath($path)
        );
    }

    /**
     * @dataProvider getDataForTestPrefixDirectoryPath
     */
    public function testPrefixDirectoryPath(string $expectedPrefixedPath, string $path): void
    {
        self::assertSame(
            $expectedPrefixedPath,
            $this->getPrefixer()->prefixDirectoryPath($path)
        );
    }

    /**
     * @dataProvider getDataForTestStripPrefixPath
     */
    public function testStripPrefix(string $expectedStrippedPath, string $path): void
    {
        self::assertSame(
            $expectedStrippedPath,
            $this->getPrefixer()->stripPrefix($path)
        );
    }

    /**
     * @dataProvider getDataForTestStripDirectoryPrefix
     */
    public function testStripDirectoryPrefix(string $expectedStrippedPath, string $path): void
    {
        self::assertSame(
            $expectedStrippedPath,
            $this->getPrefixer()->stripDirectoryPrefix($path)
        );
    }
}
