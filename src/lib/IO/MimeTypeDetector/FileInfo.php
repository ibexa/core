<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\MimeTypeDetector;

use finfo;
use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\IO\MimeTypeDetector;

class FileInfo implements MimeTypeDetector
{
    /**
     * Magic FileInfo object.
     */
    protected finfo $fileInfo;

    /**
     * Checks for the required ext/fileinfo.
     */
    public function __construct()
    {
        // Enabled by default since 5.3. Still checking if someone disabled
        // manually.
        if (!extension_loaded('fileinfo')) {
            throw new \RuntimeException('The extension "ext/fileinfo" must be loaded in order for this class to work.');
        }
    }

    public function getFromPath(string $path): string
    {
        $fileInfo = $this->getFileInfo()->file($path);
        if ($fileInfo === false) {
            throw new InvalidArgumentException('$path', "Failed to get file information for the path '$path'");
        }

        return $fileInfo;
    }

    public function getFromBuffer(string $buffer): string
    {
        $bufferInfo = $this->getFileInfo()->buffer($buffer);
        if ($bufferInfo === false) {
            throw new InvalidArgumentException('$path', 'Failed to get file information a string buffer');
        }

        return $bufferInfo;
    }

    /**
     * Creates a new (or re-uses) finfo object and returns it.
     */
    protected function getFileInfo(): finfo
    {
        if (!isset($this->fileInfo)) {
            $this->fileInfo = new finfo(FILEINFO_MIME_TYPE);
        }

        return $this->fileInfo;
    }
}
