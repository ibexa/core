<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\Adapter;

use Ibexa\Core\IO\Flysystem\Adapter\DynamicPathFilesystemAdapterDecorator;
use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\IO\Flysystem\Adapter\DynamicPathFilesystemAdapterDecorator
 *
 * Note: SiteAccess-aware dynamic settings resolving aspect has been tested via PathPrefixer
 * and Visibility converter test cases:
 * {@see \Ibexa\Tests\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverterTestCase}
 * {@see \Ibexa\Tests\Core\IO\Flysystem\PathPrefixer\DFSSiteAccessAwarePathPrefixerTest}
 */
final class DynamicPathFilesystemAdapterDecoratorTest extends TestCase
{
    private const FLYSYSTEM_TEST_DIR = __DIR__;
    private const FILE_CONTENTS = 'FOO BAR';
    private const FOO_BAR_FILE = 'foo/bar.file';
    private const BAR_COPY_FILE = '/bar.copy';
    public const MY_DIR_NAME = '/my_dir';

    private FilesystemAdapter $adapter;

    private Config $config;

    /** @var \League\Flysystem\FilesystemAdapter&\PHPUnit\Framework\MockObject\MockObject */
    private FilesystemAdapter $innerAdapterMock;

    protected function setUp(): void
    {
        $this->innerAdapterMock = $this->createMock(FilesystemAdapter::class);
        $this->adapter = new DynamicPathFilesystemAdapterDecorator(
            $this->innerAdapterMock,
            $this->createPathPrefixerMock()
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
    public function testWrite(): void
    {
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('write')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE),
                self::FILE_CONTENTS,
                $this->config
            );

        $this->adapter->write(self::FOO_BAR_FILE, self::FILE_CONTENTS, $this->config);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testWriteStream(): void
    {
        $resource = tmpfile();

        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('writeStream')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE),
                $resource,
                $this->config
            );

        $this->adapter->writeStream(
            self::FOO_BAR_FILE,
            $resource,
            $this->config
        );

        fclose($resource);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testFileSize(): void
    {
        $fileSize = 123;
        $fileAttributesMock = $this->createFileAttributesMock('fileSize', $fileSize);

        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('fileSize')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE)
            )
            ->willReturn($fileAttributesMock);

        self::assertSame(
            $fileSize,
            $this->adapter->fileSize(self::FOO_BAR_FILE)->fileSize()
        );
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testFileExists(): void
    {
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('fileExists')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE)
            )
            ->willReturn(true);

        self::assertTrue($this->adapter->fileExists(self::FOO_BAR_FILE));
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testLastModified(): void
    {
        $lastModified = 1673264474;
        $fileAttributesMock = $this->createFileAttributesMock('lastModified', $lastModified);

        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('lastModified')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE)
            )
            ->willReturn($fileAttributesMock);

        self::assertSame(
            $lastModified,
            $this->adapter->lastModified(self::FOO_BAR_FILE)->lastModified()
        );
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testCopy(): void
    {
        $relativeCopiedFilePath = self::BAR_COPY_FILE;

        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('copy')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE),
                $this->buildAbsolutePath($relativeCopiedFilePath),
                $this->config
            );

        $this->adapter->copy(self::FOO_BAR_FILE, $relativeCopiedFilePath, $this->config);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testMove(): void
    {
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('move')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE),
                $this->buildAbsolutePath(self::BAR_COPY_FILE),
                $this->config
            );

        $this->adapter->move(self::FOO_BAR_FILE, self::BAR_COPY_FILE, $this->config);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testListContents(): void
    {
        $contents = [
            self::FOO_BAR_FILE,
            self::BAR_COPY_FILE,
        ];

        $fileAttributesMocks = [];
        foreach ($contents as $relativeFilePath) {
            $fileAttributesMock = $this->createFileAttributesPathMock(
                $this->buildAbsolutePath($relativeFilePath)
            );
            $fileAttributesMock
                ->expects(self::once())
                ->method('withPath')
                ->willReturn($this->createFileAttributesPathMock($relativeFilePath));

            $fileAttributesMocks[] = $fileAttributesMock;
        }

        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('listContents')
            ->with(
                $this->buildAbsolutePath('/'),
                true
            )->willReturn($fileAttributesMocks);

        $rootDirectoryContents = $this->adapter->listContents('/', true);
        foreach ($rootDirectoryContents as $storageAttributes) {
            self::assertContains($storageAttributes->path(), $contents);
        }
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testRead(): void
    {
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('read')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE)
            )
            ->willReturn(self::FILE_CONTENTS);

        self::assertSame(self::FILE_CONTENTS, $this->adapter->read(self::FOO_BAR_FILE));
    }

    /**
     * @depends testWrite
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testReadStream(): void
    {
        $fileHandle = tmpfile();
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('readStream')
            ->with(
                $this->buildAbsolutePath(self::FOO_BAR_FILE)
            )
            ->willReturn($fileHandle);

        self::assertSame($fileHandle, $this->adapter->readStream(self::FOO_BAR_FILE));

        fclose($fileHandle);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testCreateDirectory(): void
    {
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('createDirectory')
            ->with(
                $this->buildAbsolutePath(self::MY_DIR_NAME),
                $this->config
            );

        $this->adapter->createDirectory(self::MY_DIR_NAME, $this->config);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testDeleteDirectory(): void
    {
        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('deleteDirectory')
            ->with(
                $this->buildAbsolutePath(self::MY_DIR_NAME)
            );

        $this->adapter->deleteDirectory(self::MY_DIR_NAME);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function testVisibility(): void
    {
        $relativeFilePath = self::FOO_BAR_FILE;
        $fileAttributesMock = $this->createFileAttributesMock(
            'visibility',
            Visibility::PUBLIC
        );

        $this
            ->innerAdapterMock
            ->expects(self::once())
            ->method('visibility')
            ->with(
                $this->buildAbsolutePath($relativeFilePath)
            )
            ->willReturn($fileAttributesMock);

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
                    return str_replace(
                        ltrim(self::FLYSYSTEM_TEST_DIR, '/') . '/',
                        '',
                        $absolutePath
                    );
                }
            );

        return $prefixerMock;
    }

    private function buildAbsolutePath(string $relativeFilePath): string
    {
        return self::FLYSYSTEM_TEST_DIR . '/' . $relativeFilePath;
    }

    private function createFileAttributesPathMock(string $path): MockObject
    {
        return $this->createFileAttributesMock('path', $path);
    }

    /**
     * @param string|int $returnValue
     */
    private function createFileAttributesMock(string $methodName, $returnValue): MockObject
    {
        $fileAttributesMock = $this->createMock(FileAttributes::class);
        $fileAttributesMock
            ->expects(self::once())
            ->method($methodName)
            ->willReturn($returnValue);

        return $fileAttributesMock;
    }
}
