<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\MimeTypeDetector;

use Ibexa\Contracts\Core\IO\MimeTypeDetector;

class FileInfo implements MimeTypeDetector
{
    /**
     * Magic FileInfo object.
     *
     * @var \finfo
     */
    protected $fileInfo;

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

    public function getFromPath($path): string|false
    {
        return $this->getFileInfo()->file($path);
    }

    public function getFromBuffer($path): string|false
    {
        return $this->getFileInfo()->buffer($path);
    }

    /**
     * Creates a new (or re-uses) finfo object and returns it.
     *
     * @return \finfo
     */
    protected function getFileInfo()
    {
        if (!isset($this->fileInfo)) {
            $this->fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        }

        return $this->fileInfo;
    }
}
