<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\IOMetadataHandler;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\IO\BinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\IOMetadataHandler;
use Ibexa\Core\IO\UrlDecorator;

/**
 * Manages IO metadata in a mysql table, ibexa_dfs_file.
 *
 * It will prevent simultaneous writes to the same file.
 *
 * @phpstan-type TLegacyDFSBinaryFileData array{id: string, name_hash: string, name: string, name_trunk: string, datatype: string, scope: string, size: int, mtime: int, expired: bool, status: bool}
 */
class LegacyDFSCluster implements IOMetadataHandler
{
    public const string DFS_FILE_TABLE = 'ibexa_dfs_file';

    private Connection $db;

    private ?UrlDecorator $urlDecorator;

    /**
     * @param Connection $connection Doctrine DBAL connection
     * @param UrlDecorator|null $urlDecorator The URL decorator used to add a prefix to files path
     */
    public function __construct(
        Connection $connection,
        ?UrlDecorator $urlDecorator = null
    ) {
        $this->db = $connection;
        $this->urlDecorator = $urlDecorator;
    }

    /**
     * Inserts a new reference to file $spiBinaryFileId.
     *
     * @param BinaryFileCreateStruct $spiBinaryFileCreateStruct
     *
     * @return BinaryFile
     *
     * @throws Exception
     */
    public function create(SPIBinaryFileCreateStruct $spiBinaryFileCreateStruct): SPIBinaryFile
    {
        $path = $this->addPrefix($spiBinaryFileCreateStruct->id);
        $params = [
            'name' => $path,
            'name_hash' => md5($path),
            'name_trunk' => $this->getNameTrunk($spiBinaryFileCreateStruct),
            'mtime' => $spiBinaryFileCreateStruct->mtime->getTimestamp(),
            'size' => $spiBinaryFileCreateStruct->size,
            'scope' => $this->getScope($spiBinaryFileCreateStruct),
            'datatype' => $spiBinaryFileCreateStruct->mimeType,
        ];

        try {
            $this->db->insert(self::DFS_FILE_TABLE, $params);
        } catch (Exception $e) {
            $this->db->update(self::DFS_FILE_TABLE, [
                'mtime' => $params['mtime'],
                'size' => $params['size'],
                'scope' => $params['scope'],
                'datatype' => $params['datatype'],
            ], [
                'name_hash' => $params['name_hash'],
            ]);
        }

        return $this->mapSPIBinaryFileCreateStructToSPIBinaryFile($spiBinaryFileCreateStruct);
    }

    /**
     * Deletes file $spiBinaryFileId.
     *
     * @param string $binaryFileId
     *
     * @throws Exception
     * @throws BinaryFileNotFoundException If $spiBinaryFileId is not found
     */
    public function delete(string $binaryFileId): void
    {
        $path = (string)$this->addPrefix($binaryFileId);

        // Unlike the legacy cluster, the file is directly deleted. It was inherited from the DB cluster anyway
        $affectedRows = (int)$this->db->delete(self::DFS_FILE_TABLE, [
            'name_hash' => md5($path),
        ]);

        if ($affectedRows !== 1) {
            // Is this really necessary ?
            throw new BinaryFileNotFoundException($path);
        }
    }

    /**
     * Loads and returns metadata for $spiBinaryFileId.
     *
     * @throws \DateMalformedStringException
     * @throws BinaryFileNotFoundException if no row is found for $spiBinaryFileId
     * @throws Exception Any unhandled DBAL exception
     */
    public function load(string $spiBinaryFileId): SPIBinaryFile
    {
        $path = $this->addPrefix($spiBinaryFileId);

        $queryBuilder = $this->db->createQueryBuilder();
        $result = $queryBuilder
            ->select(
                'e.name_hash',
                'e.name',
                'e.name_trunk',
                'e.datatype',
                'e.scope',
                'e.size',
                'e.mtime',
                'e.expired',
                'e.status',
            )
            ->from(self::DFS_FILE_TABLE, 'e')
            ->andWhere('e.name_hash = :name_hash')
            ->andWhere('e.expired != true')
            ->andWhere('e.mtime > 0')
            ->setParameter('name_hash', md5($path))
            ->executeQuery()
        ;

        if ($result->rowCount() === 0) {
            throw new BinaryFileNotFoundException($path);
        }

        /** @phpstan-var TLegacyDFSBinaryFileData $row */
        $row = $result->fetchAssociative() + ['id' => $spiBinaryFileId];

        return $this->mapArrayToSPIBinaryFile($row);
    }

    /**
     * Checks if a file $spiBinaryFileId exists.
     *
     * @throws Exception Any unhandled DBAL exception
     */
    public function exists(string $spiBinaryFileId): bool
    {
        $path = $this->addPrefix($spiBinaryFileId);

        $queryBuilder = $this->db->createQueryBuilder();
        $result = $queryBuilder
            ->select(
                'e.name_hash',
                'e.name',
                'e.name_trunk',
                'e.datatype',
                'e.scope',
                'e.size',
                'e.mtime',
                'e.expired',
                'e.status',
            )
            ->from(self::DFS_FILE_TABLE, 'e')
            ->andWhere('e.name_hash = :name_hash')
            ->andWhere('e.expired != true')
            ->andWhere('e.mtime > 0')
            ->setParameter('name_hash', md5($path))
            ->executeQuery()
        ;

        return $result->rowCount() === 1;
    }

    protected function getNameTrunk(SPIBinaryFileCreateStruct $binaryFileCreateStruct): string
    {
        return $this->addPrefix($binaryFileCreateStruct->id);
    }

    /**
     * Returns the value for the scope meta field, based on the created file's path.
     *
     * Note that this is slightly incorrect, as it will return binaryfile for media files as well. It is a bit
     * of an issue, but shouldn't be a blocker given that this meta field isn't used that much.
     *
     * @param BinaryFileCreateStruct $binaryFileCreateStruct
     *
     * @return string
     */
    protected function getScope(SPIBinaryFileCreateStruct $binaryFileCreateStruct): string
    {
        [$filePrefix] = explode('/', $binaryFileCreateStruct->id);

        return match ($filePrefix) {
            'images' => 'image',
            'original' => 'binaryfile',
            default => 'UNKNOWN_SCOPE',
        };
    }

    protected function addPrefix(string $id): string
    {
        return isset($this->urlDecorator) ? $this->urlDecorator->decorate($id) : $id;
    }

    /**
     * Removes the internal prefix string from $prefixedId.
     *
     * @return string the id without the prefix
     */
    protected function removePrefix(string $prefixedId): string
    {
        return isset($this->urlDecorator) ? $this->urlDecorator->undecorate($prefixedId) : $prefixedId;
    }

    /**
     * @throws BinaryFileNotFoundException
     * @throws Exception
     */
    public function getMimeType(string $spiBinaryFileId): string
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $result = $queryBuilder
            ->select('e.datatype')
            ->from(self::DFS_FILE_TABLE, 'e')
            ->andWhere('e.name_hash = :name_hash')
            ->andWhere('e.expired != true')
            ->andWhere('e.mtime > 0')
            ->setParameter('name_hash', md5($this->addPrefix($spiBinaryFileId)))
            ->executeQuery()
        ;

        if ($result->rowCount() === 0) {
            throw new BinaryFileNotFoundException($spiBinaryFileId);
        }

        /** @var array{datatype: string} $row */
        $row = $result->fetchAssociative();

        return $row['datatype'];
    }

    /**
     * Delete directory and all the content under specified directory.
     *
     * @param string $path persistence path, not prefixed by URL decoration
     *
     * @throws Exception
     */
    public function deleteDirectory(string $path): void
    {
        $query = $this->db->createQueryBuilder();
        $query
            ->delete(self::DFS_FILE_TABLE)
            ->where('name LIKE :spiPath ESCAPE :esc')
            ->setParameter('esc', '\\')
            ->setParameter(
                'spiPath',
                addcslashes($this->addPrefix(rtrim($path, '/')), '%_') . '/%'
            );
        $query->executeStatement();
    }

    /**
     * Maps an array of database properties (id, size, mtime, datatype, md5_path, path...) to an SPIBinaryFile object.
     *
     * @param array{id: string, size: int, mtime: int} $properties database properties array
     *
     * @throws \DateMalformedStringException
     */
    protected function mapArrayToSPIBinaryFile(array $properties): SPIBinaryFile
    {
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $properties['id'];
        $spiBinaryFile->size = $properties['size'];
        $spiBinaryFile->mtime = new DateTime('@' . $properties['mtime']);

        return $spiBinaryFile;
    }

    protected function mapSPIBinaryFileCreateStructToSPIBinaryFile(SPIBinaryFileCreateStruct $binaryFileCreateStruct): SPIBinaryFile
    {
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $binaryFileCreateStruct->id;
        $spiBinaryFile->mtime = $binaryFileCreateStruct->mtime;
        $spiBinaryFile->size = $binaryFileCreateStruct->size;

        return $spiBinaryFile;
    }
}
