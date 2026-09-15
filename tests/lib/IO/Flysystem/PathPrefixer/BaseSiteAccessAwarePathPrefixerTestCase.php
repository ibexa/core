<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\PathPrefixer;

use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;
use Ibexa\Tests\Core\Search\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PathPrefixerInterface::class)]
abstract class BaseSiteAccessAwarePathPrefixerTestCase extends TestCase
{
    abstract protected function getPrefixer(): PathPrefixerInterface;

    /**
     * @return iterable<string, array{string, string}>
     */
    abstract public static function getDataForTestPrefixPath(): iterable;

    /**
     * @return iterable<string, array{string, string}>
     */
    abstract public static function getDataForTestPrefixDirectoryPath(): iterable;

    /**
     * @return iterable<string, array{string, string}>
     */
    abstract public static function getDataForTestStripPrefixPath(): iterable;

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function getDataForTestStripDirectoryPrefix(): iterable
    {
        // treat file names as directories
        yield from static::getDataForTestStripPrefixPath();
    }

    #[DataProvider('getDataForTestPrefixPath')]
    final public function testPrefixPath(string $expectedPrefixedPath, string $path): void
    {
        self::assertSame(
            $expectedPrefixedPath,
            $this->getPrefixer()->prefixPath($path)
        );
    }

    #[DataProvider('getDataForTestPrefixDirectoryPath')]
    final public function testPrefixDirectoryPath(string $expectedPrefixedPath, string $path): void
    {
        self::assertSame(
            $expectedPrefixedPath,
            $this->getPrefixer()->prefixDirectoryPath($path)
        );
    }

    #[DataProvider('getDataForTestStripPrefixPath')]
    final public function testStripPrefix(string $expectedStrippedPath, string $path): void
    {
        self::assertSame(
            $expectedStrippedPath,
            $this->getPrefixer()->stripPrefix($path)
        );
    }

    #[DataProvider('getDataForTestStripDirectoryPrefix')]
    final public function testStripDirectoryPrefix(string $expectedStrippedPath, string $path): void
    {
        self::assertSame(
            $expectedStrippedPath,
            $this->getPrefixer()->stripDirectoryPrefix($path)
        );
    }
}
