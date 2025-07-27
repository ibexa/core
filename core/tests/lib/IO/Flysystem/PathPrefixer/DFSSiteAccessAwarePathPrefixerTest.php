<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\PathPrefixer;

use Ibexa\Contracts\Core\SiteAccess\ConfigProcessor;
use Ibexa\Core\IO\Flysystem\PathPrefixer\DFSSiteAccessAwarePathPrefixer;
use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;

/**
 * @covers \Ibexa\Core\IO\Flysystem\PathPrefixer\DFSSiteAccessAwarePathPrefixer
 */
final class DFSSiteAccessAwarePathPrefixerTest extends BaseSiteAccessAwarePathPrefixerTestCase
{
    public function getDataForTestPrefixPath(): iterable
    {
        $dfsRootDir = $this->getDFSRootDir();

        yield 'dynamic path to relative file name' => [$dfsRootDir . '/var/storage/foo', 'foo'];
        yield 'dynamic path to relative file path name' => [
            $dfsRootDir . '/var/storage/foo/bar',
            'foo/bar',
        ];
        yield 'dynamic path to absolute file name' => [$dfsRootDir . '/var/storage/foo', '/foo'];
    }

    public function getDataForTestPrefixDirectoryPath(): iterable
    {
        $dfsRootDir = $this->getDFSRootDir();

        yield 'dynamic path to relative directory' => [
            $dfsRootDir . '/var/storage/foo/',
            'foo/',
        ];
        yield 'dynamic path to absolute directory' => [
            $dfsRootDir . '/var/storage/foo/',
            '/foo/',
        ];
        yield 'dynamic path to relative directory, no trailing separator' => [
            $dfsRootDir . '/var/storage/foo/',
            'foo',
        ];
        yield 'dynamic path to absolute directory, no trailing separator' => [
            $dfsRootDir . '/var/storage/foo/',
            '/foo',
        ];
    }

    public function getDataForTestStripPrefixPath(): iterable
    {
        $dfsRootDir = $this->getDFSRootDir();

        yield 'relative single file name' => ['/foo', $dfsRootDir . '/var/storage/foo'];
        yield 'relative file path' => ['/foo/bar', $dfsRootDir . '/var/storage/foo/bar'];
    }

    protected function getPrefixer(): PathPrefixerInterface
    {
        $dynamicPath = '$var_dir$/$storage_dir$/';
        $configProcessor = $this->createMock(ConfigProcessor::class);
        $configProcessor
            ->method('processSettingValue')
            ->with($dynamicPath)
            ->willReturn('var/storage');

        return new DFSSiteAccessAwarePathPrefixer(
            $configProcessor,
            $this->getDFSRootDir(),
            $dynamicPath
        );
    }

    private function getDFSRootDir(): string
    {
        return sys_get_temp_dir() . '/dfs';
    }
}
