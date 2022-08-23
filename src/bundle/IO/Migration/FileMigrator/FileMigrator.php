<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\IO\Migration\FileMigrator;

use Ibexa\Bundle\IO\Migration\FileMigratorInterface;
use Ibexa\Bundle\IO\Migration\MigrationHandler;
use Ibexa\Contracts\Core\IO\BinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;

final class FileMigrator extends MigrationHandler implements FileMigratorInterface
{
    public function migrateFile(BinaryFile $binaryFile)
    {
        if (!$this->migrateBinaryFile($binaryFile) || !$this->migrateMetadata($binaryFile)) {
            return false;
        }

        $this->logInfo("Successfully migrated: '{$binaryFile->id}'");

        return true;
    }

    private function migrateBinaryFile(BinaryFile $binaryFile): bool
    {
        if ($this->fromBinarydataHandler === $this->toBinarydataHandler) {
            return true;
        }

        try {
            $binaryFileResource = $this->fromBinarydataHandler->getResource($binaryFile->id);
        } catch (BinaryFileNotFoundException $e) {
            $this->logError("Cannot load binary data for: '{$binaryFile->id}'. Error: " . $e->getMessage());

            return false;
        }

        $binaryFileCreateStruct = new BinaryFileCreateStruct();
        $binaryFileCreateStruct->id = $binaryFile->id;
        $binaryFileCreateStruct->setInputStream($binaryFileResource);

        try {
            $this->toBinarydataHandler->create($binaryFileCreateStruct);
        } catch (\RuntimeException $e) {
            $this->logError("Cannot migrate binary data for: '{$binaryFile->id}'. Error: " . $e->getMessage());

            return false;
        }

        return true;
    }

    private function migrateMetadata(BinaryFile $binaryFile): bool
    {
        if ($this->fromMetadataHandler === $this->toMetadataHandler) {
            return true;
        }

        $metadataCreateStruct = new BinaryFileCreateStruct();
        $metadataCreateStruct->id = $binaryFile->id;
        $metadataCreateStruct->size = $binaryFile->size;
        $metadataCreateStruct->mtime = $binaryFile->mtime;
        $metadataCreateStruct->mimeType = $this->fromMetadataHandler->getMimeType($binaryFile->id);

        try {
            $this->toMetadataHandler->create($metadataCreateStruct);
        } catch (\RuntimeException $e) {
            $this->logError("Cannot migrate metadata for: '{$binaryFile->id}'. Error: " . $e->getMessage() . $e->getPrevious()->getMessage());

            return false;
        }

        return true;
    }
}

class_alias(FileMigrator::class, 'eZ\Bundle\EzPublishIOBundle\Migration\FileMigrator\FileMigrator');
