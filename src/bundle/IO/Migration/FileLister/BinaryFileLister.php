<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\IO\Migration\FileLister;

use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Bundle\IO\Migration\FileListerInterface;
use Ibexa\Bundle\IO\Migration\MigrationHandler;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Iterator;
use LimitIterator;
use Psr\Log\LoggerInterface;

class BinaryFileLister extends MigrationHandler implements FileListerInterface
{
    private FileIteratorInterface $fileList;

    private string $filesDir;

    public function __construct(
        HandlerRegistry $metadataHandlerRegistry,
        HandlerRegistry $binarydataHandlerRegistry,
        Iterator $fileList,
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

    public function loadMetadataList($limit = null, $offset = null): array
    {
        $metadataList = [];
        $fileLimitList = new LimitIterator($this->fileList, $offset, $limit);

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

class_alias(BinaryFileLister::class, 'eZ\Bundle\EzPublishIOBundle\Migration\FileLister\BinaryFileLister');
