<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\PathPrefixer;

use Ibexa\Core\IO\Flysystem\PathPrefixer\LocalSiteAccessAwarePathPrefixer;
use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;
use Ibexa\Core\IO\IOConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LocalSiteAccessAwarePathPrefixer::class)]
final class LocalSiteAccessAwarePathPrefixerTest extends BaseSiteAccessAwarePathPrefixerTestCase
{
    public static function getDataForTestPrefixPath(): iterable
    {
        yield 'dynamic path to relative file name' => [__DIR__ . '/var/storage/foo', 'foo'];
        yield 'dynamic path to relative file path name' => [
            __DIR__ . '/var/storage/foo/bar',
            'foo/bar',
        ];
        yield 'dynamic path to absolute file name' => [__DIR__ . '/var/storage/foo', '/foo'];
    }

    public static function getDataForTestPrefixDirectoryPath(): iterable
    {
        yield 'dynamic path to relative directory' => [__DIR__ . '/var/storage/foo/', 'foo/'];
        yield 'dynamic path to absolute directory' => [__DIR__ . '/var/storage/foo/', '/foo/'];
        yield 'dynamic path to relative directory, no trailing separator' => [
            __DIR__ . '/var/storage/foo/',
            'foo',
        ];
        yield 'dynamic path to absolute directory, no trailing separator' => [
            __DIR__ . '/var/storage/foo/',
            '/foo',
        ];
    }

    public static function getDataForTestStripPrefixPath(): iterable
    {
        yield 'relative single file name' => ['/foo', __DIR__ . '/var/storage/foo'];
        yield 'relative file path' => ['/foo/bar', __DIR__ . '/var/storage/foo/bar'];
    }

    protected function getPrefixer(): PathPrefixerInterface
    {
        $configProviderMock = $this->createMock(IOConfigProvider::class);
        $configProviderMock
            ->method('getRootDir')
            ->willReturn(__DIR__ . '/var/storage');

        return new LocalSiteAccessAwarePathPrefixer(
            $configProviderMock
        );
    }
}
