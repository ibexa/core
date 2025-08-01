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

    /** @var string Directory where files are stored, within the storage dir. Example: 'original' */
    private string $filesDir;

    /**
     * @param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOMetadataHandler> $metadataHandlerRegistry
     * @param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOBinarydataHandler> $binarydataHandlerRegistry
     * @param string $filesDir Directory where files are stored, within the storage dir. Example: 'original'
     */
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

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadMetadataList(?int $limit = null, ?int $offset = null): array
    {
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
