<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration\FileLister;

use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Bundle\IO\Migration\FileListerInterface;
use Ibexa\Bundle\IO\Migration\MigrationHandler;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use LimitIterator;
use Psr\Log\LoggerInterface;

class BinaryFileLister extends MigrationHandler implements FileListerInterface
{
    private FileIteratorInterface $fileList;

    private string $filesDir;

    public function __construct(
        HandlerRegistry $metadataHandlerRegistry,
        HandlerRegistry $binarydataHandlerRegistry,
        FileIteratorInterface $fileList,
        string $filesDir,
        ?LoggerInterface $logger = null
    ) {
        $this->fileList = $fileList;
        $this->filesDir = $filesDir;

        $this->fileList->rewind();

        parent::__construct($metadataHandlerRegistry, $binarydataHandlerRegistry, $logger);
    }

    public function countFiles(): int
    {
        return count($this->fileList);
    }

    public function loadMetadataList(
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $metadataList = [];
        $fileLimitList = new LimitIterator($this->fileList, $offset ?? 0, $limit ?? -1);

        foreach ($fileLimitList as $fileId) {
            try {
                $metadataList[] = $this->fromMetadataHandler->load($this->filesDir . '/' . $fileId);
            } catch (BinaryFileNotFoundException $e) {
                $this->logMissingFile($fileId);

                continue;
            }
        }

        return $metadataList;
    }
}
