<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\Adapter;

use Ibexa\Core\IO\Flysystem\PathPrefixer\PathPrefixerInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

/***
 * @internal
 */
final class DynamicPathFilesystemAdapterDecorator implements FilesystemAdapter
{
    private FilesystemAdapter $innerAdapter;

    private PathPrefixerInterface $prefixer;

    public function __construct(FilesystemAdapter $innerAdapter, PathPrefixerInterface $prefixer)
    {
        $this->innerAdapter = $innerAdapter;
        $this->prefixer = $prefixer;
    }

    public function fileExists(string $path): bool
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->prefixer->prefixPath($path);

        $this->innerAdapter->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $path = $this->prefixer->prefixPath($path);

        $this->innerAdapter->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->read($path);
    }

    public function readStream(string $path)
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->readStream($path);
    }

    public function delete(string $path): void
    {
        $path = $this->prefixer->prefixPath($path);

        $this->innerAdapter->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $path = $this->prefixer->prefixPath($path);

        $this->innerAdapter->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $path = $this->prefixer->prefixPath($path);

        $this->innerAdapter->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $path = $this->prefixer->prefixPath($path);

        $this->innerAdapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        $path = $this->prefixer->prefixPath($path);

        return $this->innerAdapter->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $path = $this->prefixer->prefixPath($path);

        foreach ($this->innerAdapter->listContents($path, $deep) as $storageAttributes) {
            $itemPath = $this->prefixer->stripPrefix($storageAttributes->path());

            yield $storageAttributes->withPath($itemPath);
        }

        yield from [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $source = $this->prefixer->prefixPath($source);
        $destination = $this->prefixer->prefixPath($destination);

        $this->innerAdapter->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->prefixer->prefixPath($source);
        $destinationPath = $this->prefixer->prefixPath($destination);

        $this->innerAdapter->copy($sourcePath, $destinationPath, $config);
    }
}
