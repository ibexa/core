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
abstract class BaseSiteAccessAwarePathPrefixerTestCase extends TestCase
{
    abstract protected function getPrefixer(): PathPrefixerInterface;

    /**
     * @return iterable<string, array{string, string}>
     */
    abstract public function getDataForTestPrefixPath(): iterable;

    /**
     * @return iterable<string, array{string, string}>
     */
    abstract public function getDataForTestPrefixDirectoryPath(): iterable;

    /**
     * @return iterable<string, array{string, string}>
     */
    abstract public function getDataForTestStripPrefixPath(): iterable;

    /**
     * @return iterable<string, array{string, string}>
     */
    public function getDataForTestStripDirectoryPrefix(): iterable
    {
        // treat file names as directories
        yield from $this->getDataForTestStripPrefixPath();
    }

    /**
     * @dataProvider getDataForTestPrefixPath
     */
    final public function testPrefixPath(string $expectedPrefixedPath, string $path): void
    {
        self::assertSame(
            $expectedPrefixedPath,
            $this->getPrefixer()->prefixPath($path)
        );
    }

    /**
     * @dataProvider getDataForTestPrefixDirectoryPath
     */
    final public function testPrefixDirectoryPath(string $expectedPrefixedPath, string $path): void
    {
        self::assertSame(
            $expectedPrefixedPath,
            $this->getPrefixer()->prefixDirectoryPath($path)
        );
    }

    /**
     * @dataProvider getDataForTestStripPrefixPath
     */
    final public function testStripPrefix(string $expectedStrippedPath, string $path): void
    {
        self::assertSame(
            $expectedStrippedPath,
            $this->getPrefixer()->stripPrefix($path)
        );
    }

    /**
     * @dataProvider getDataForTestStripDirectoryPrefix
     */
    final public function testStripDirectoryPrefix(string $expectedStrippedPath, string $path): void
    {
        self::assertSame(
            $expectedStrippedPath,
            $this->getPrefixer()->stripDirectoryPrefix($path)
        );
    }
}
