<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\Adapter;

use Ibexa\Core\IO\Flysystem\Adapter\LocalSiteAccessAwareFilesystemAdapter;
use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\MimeTypeDetector;
use PHPUnit\Framework\TestCase;

/**
 * Test proxying of Flysystem's {@see \League\Flysystem\Local\LocalFilesystemAdapter} methods by
 * {@see \Ibexa\Core\IO\Flysystem\Adapter\LocalSiteAccessAwareFilesystemAdapter}.
 *
 * Note: SiteAccess-aware aspect has been tested via PathPrefixer and Visibility converter test cases:
 * {@see \Ibexa\Tests\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverterTest}
 * {@see \Ibexa\Tests\Core\IO\Flysystem\PathPrefixer\DFSSiteAccessAwarePathPrefixerTest}
 */
final class LocalSiteAccessAwareFilesystemAdapterTest extends TestCase
{
    private const FLYSYSTEM_TEST_DIR = __DIR__ . '/_flysystem';
    private const FILE_CONTENTS = 'FOO BAR';

    private FilesystemAdapter $adapter;

    private Config $config;

    /**
     * Local Filesystem for these tests.
     */
    private static Filesystem $fileSystem;

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public static function setUpBeforeClass(): void
    {
        self::$fileSystem = new Filesystem(new LocalFilesystemAdapter(self::FLYSYSTEM_TEST_DIR));
        self::$fileSystem->deleteDirectory('.');
        self::$fileSystem->createDirectory('.');
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public static function tearDownAfterClass(): void
    {
        // delete root directory created for tests
        self::$fileSystem->deleteDirectory('.');
    }

    protected function setUp(): void
    {
        $this->adapter = new LocalSiteAccessAwareFilesystemAdapter(
            self::FLYSYSTEM_TEST_DIR,
            new PortableVisibilityConverter(),
            $this->createPathPrefixerMock(),
            $this->createMock(MimeTypeDetector::class)
        );

        $this->config = new Config(
            [
                Config::OPTION_VISIBILITY => Visibility::PUBLIC,
                Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC,
            ]
        );
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testWrite(): string
    {
        $relativeFilePath = 'foo/bar.file';
        $this->adapter->write($relativeFilePath, self::FILE_CONTENTS, $this->config);

        self::assertFileExists($this->buildAbsolutePath($relativeFilePath));

        return $relativeFilePath;
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testWriteStream($sourceRelativeFilePath): void
    {
        $relativeFilePath = '/bar.file';
        $this->adapter->writeStream(
            $relativeFilePath,
            fopen($this->buildAbsolutePath($sourceRelativeFilePath), 'rb'),
            $this->config
        );
        self::assertFileExists($this->buildAbsolutePath($relativeFilePath));
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testFileSize(string $relativeFilePath): void
    {
        self::assertSame(
            filesize($this->buildAbsolutePath($relativeFilePath)),
            $this->adapter->fileSize($relativeFilePath)->fileSize()
        );
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testFileExists(string $relativeFilePath): void
    {
        self::assertTrue($this->adapter->fileExists($relativeFilePath));
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testLastModified(string $relativeFilePath): void
    {
        self::assertSame(
            filemtime($this->buildAbsolutePath($relativeFilePath)),
            $this->adapter->lastModified($relativeFilePath)->lastModified()
        );
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testCopy(string $relativeFilePath): string
    {
        $relativeCopiedFilePath = '/bar.copy';
        $this->adapter->copy($relativeFilePath, $relativeCopiedFilePath, $this->config);
        self::assertFileExists($this->buildAbsolutePath($relativeCopiedFilePath));

        return $relativeFilePath;
    }

    /**
     * @depends testCopy
     * @depends testRead
     * @depends testReadStream
     * @depends testVisibility
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testMove(string $relativeFilePath): void
    {
        $relativeMovedFilePath = '/bar.copy';
        $this->adapter->move($relativeFilePath, $relativeMovedFilePath, $this->config);
        self::assertFileExists($this->buildAbsolutePath($relativeMovedFilePath));
        self::assertFileNotExists($this->buildAbsolutePath($relativeFilePath));
    }

    /**
     * @depends testCopy
     * @depends testWriteStream
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testListContents(): void
    {
        $expectedRootDirectoryContents = [
            '/foo',
            '/foo/bar.file',
            '/bar.file',
            '/bar.copy',
        ];
        $rootDirectoryContents = $this->adapter->listContents('/', true);
        foreach ($rootDirectoryContents as $storageAttributes) {
            self::assertContains($storageAttributes->path(), $expectedRootDirectoryContents);
        }
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testRead(string $relativeFilePath): void
    {
        self::assertSame(self::FILE_CONTENTS, $this->adapter->read($relativeFilePath));
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testReadStream(string $relativeFilePath): void
    {
        $fileHandle = $this->adapter->readStream($relativeFilePath);
        self::assertIsResource($fileHandle);
        // try to read more than expected to make sure there's no trailing data
        $contents = fread($fileHandle, strlen(self::FILE_CONTENTS) + 1);
        self::assertIsString($contents);
        self::assertSame(self::FILE_CONTENTS, $contents);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testCreateDirectory(): string
    {
        $directoryPathName = '/my_dir';
        $this->adapter->createDirectory($directoryPathName, $this->config);
        self::assertDirectoryExists(self::FLYSYSTEM_TEST_DIR . $directoryPathName);

        return $directoryPathName;
    }

    /**
     * @depends testCreateDirectory
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testDeleteDirectory(string $relativeDirectoryPath): void
    {
        $this->adapter->deleteDirectory($relativeDirectoryPath);
        self::assertDirectoryNotExists(self::FLYSYSTEM_TEST_DIR . $relativeDirectoryPath);
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testVisibility(string $relativeFilePath): void
    {
        self::assertSame(
            Visibility::PUBLIC,
            $this->adapter->visibility($relativeFilePath)->visibility()
        );
    }

    private function createPathPrefixerMock(): PathPrefixerInterface
    {
        $prefixerMock = $this->createMock(PathPrefixerInterface::class);
        $prefixerMock
            ->method('prefixPath')
            ->willReturnCallback(
                static function (string $relativePath): string {
                    return self::FLYSYSTEM_TEST_DIR . '/' . $relativePath;
                }
            );

        $prefixerMock
            ->method('stripPrefix')
            ->willReturnCallback(
                static function (string $absolutePath): string {
                    return str_replace(self::FLYSYSTEM_TEST_DIR . '/', '', $absolutePath);
                }
            );

        return $prefixerMock;
    }

    private function buildAbsolutePath(string $relativeFilePath): string
    {
        return self::FLYSYSTEM_TEST_DIR . '/' . $relativeFilePath;
    }
}
