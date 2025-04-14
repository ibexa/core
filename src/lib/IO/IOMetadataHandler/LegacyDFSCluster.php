<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\IOMetadataHandler;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\IO\BinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\IOMetadataHandler;
use Ibexa\Core\IO\UrlDecorator;
use LogicException;

/**
 * Manages IO metadata in a mysql table, ezdfsfile.
 *
 * It will prevent simultaneous writes to the same file.
 */
class LegacyDFSCluster implements IOMetadataHandler
{
    private const string NAME_HASH_PARAM_NAME = 'name_hash';
    private const string DFS_IS_EXPIRED_COMPARISON = 'e.expired != true';

    private Connection $db;

    private ?UrlDecorator $urlDecorator;

    public function __construct(Connection $connection, UrlDecorator $urlDecorator = null)
    {
        $this->db = $connection;
        $this->urlDecorator = $urlDecorator;
    }

    /**
     * Inserts a new reference to file $spiBinaryFileId.
     *
     * @param \Ibexa\Contracts\Core\IO\BinaryFileCreateStruct $spiBinaryFileCreateStruct
     *
     * @return \Ibexa\Contracts\Core\IO\BinaryFile
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \RuntimeException if a DBAL error occurs
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the $binaryFileCreateStruct is invalid
     *
     * @since 6.10 The mtime of the $binaryFileCreateStruct must be a DateTime, as specified in the struct doc.
     */
    public function create(SPIBinaryFileCreateStruct $spiBinaryFileCreateStruct): SPIBinaryFile
    {
        if (!($spiBinaryFileCreateStruct->mtime instanceof DateTime)) {
            throw new InvalidArgumentException('$binaryFileCreateStruct', 'Property \'mtime\' must be a DateTime');
        }

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
            $this->db->insert('ezdfsfile', $params);
        } catch (Exception) {
            $this->db->update('ezdfsfile', [
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
     * @param string $spiBinaryFileId
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Core\IO\Exception\BinaryFileNotFoundException If $spiBinaryFileId is not found
     */
    public function delete($spiBinaryFileId): void
    {
        $path = $this->addPrefix($spiBinaryFileId);

        // Unlike the legacy cluster, the file is directly deleted. It was inherited from the DB cluster anyway
        $affectedRows = (int)$this->db->delete('ezdfsfile', [
            'name_hash' => md5($path),
        ]);

        if ($affectedRows !== 1) {
            // Is this really necessary ?
            throw new BinaryFileNotFoundException($path);
        }
    }

    /**
     * @throws \Ibexa\Core\IO\Exception\BinaryFileNotFoundException if no row is found for $spiBinaryFileId
     * @throws \Doctrine\DBAL\Exception Any unhandled DBAL exception
     * @throws \DateMalformedStringException
     */
    public function load($spiBinaryFileId): SPIBinaryFile
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
            ->from('ezdfsfile', 'e')
            ->andWhere('e.name_hash = :' . self::NAME_HASH_PARAM_NAME)
            ->andWhere(self::DFS_IS_EXPIRED_COMPARISON)
            ->andWhere('e.mtime > 0')
            ->setParameter(self::NAME_HASH_PARAM_NAME, md5($path))
            ->executeQuery()
        ;

        if ($result->rowCount() === 0 || false === ($row = $result->fetchAssociative())) {
            throw new BinaryFileNotFoundException($path);
        }

        /** @var array{id: string, name_hash: string, name_trunk: string, datatype: string, scope: string, size: int, mtime: int, expired: bool, status: bool} $properties */
        $properties = $row + ['id' => $spiBinaryFileId];

        return $this->mapArrayToSPIBinaryFile($properties);
    }

    /**
     * @throws \Doctrine\DBAL\Exception Any unhandled DBAL exception
     */
    public function exists($spiBinaryFileId): bool
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
            ->from('ezdfsfile', 'e')
            ->andWhere('e.name_hash = :' . self::NAME_HASH_PARAM_NAME)
            ->andWhere(self::DFS_IS_EXPIRED_COMPARISON)
            ->andWhere('e.mtime > 0')
            ->setParameter(self::NAME_HASH_PARAM_NAME, md5($path))
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
     * @param \Ibexa\Contracts\Core\IO\BinaryFileCreateStruct $binaryFileCreateStruct
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

    /**
     * Adds the internal prefix string to $id.
     *
     * @return string prefixed id
     */
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
     * @param string $spiBinaryFileId
     *
     * @throws \Ibexa\Core\IO\Exception\BinaryFileNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getMimeType($spiBinaryFileId): string
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $result = $queryBuilder
            ->select('e.datatype')
            ->from('ezdfsfile', 'e')
            ->andWhere('e.name_hash = :' . self::NAME_HASH_PARAM_NAME)
            ->andWhere(self::DFS_IS_EXPIRED_COMPARISON)
            ->andWhere('e.mtime > 0')
            ->setParameter(self::NAME_HASH_PARAM_NAME, md5($this->addPrefix($spiBinaryFileId)))
            ->executeQuery()
        ;

        if ($result->rowCount() === 0) {
            throw new BinaryFileNotFoundException($spiBinaryFileId);
        }

        $dataType = $result->fetchOne();
        if (false === $dataType) {
            throw new LogicException("Failed to get mime type for $spiBinaryFileId");
        }

        return $dataType;
    }

    /**
     * Delete directory and all the content under specified directory.
     *
     * @param string $spiPath SPI Path, not prefixed by URL decoration
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteDirectory($spiPath): void
    {
        $query = $this->db->createQueryBuilder();
        $query
            ->delete('ezdfsfile')
            ->where('name LIKE :spiPath ESCAPE :esc')
            ->setParameter(':esc', '\\')
            ->setParameter(
                ':spiPath',
                addcslashes($this->addPrefix(rtrim($spiPath, '/')), '%_') . '/%'
            );
        $query->executeStatement();
    }

    /**
     * Maps an array of database properties (id, size, mtime, datatype, md5_path, path...) to an SPIBinaryFile object.
     *
     * @param array{id: string, size: int, mtime: int} $properties database properties array
     *
     * @return \Ibexa\Contracts\Core\IO\BinaryFile
     *
     * @throws \DateMalformedStringException
     */
    protected function mapArrayToSPIBinaryFile(array $properties): BinaryFile
    {
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $properties['id'];
        $spiBinaryFile->size = $properties['size'];
        $spiBinaryFile->mtime = new DateTime('@' . $properties['mtime']);

        return $spiBinaryFile;
    }

    /**
     * @param \Ibexa\Contracts\Core\IO\BinaryFileCreateStruct $binaryFileCreateStruct
     *
     * @return \Ibexa\Contracts\Core\IO\BinaryFile
     */
    protected function mapSPIBinaryFileCreateStructToSPIBinaryFile(SPIBinaryFileCreateStruct $binaryFileCreateStruct): BinaryFile
    {
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $binaryFileCreateStruct->id;
        $spiBinaryFile->mtime = $binaryFileCreateStruct->mtime;
        $spiBinaryFile->size = $binaryFileCreateStruct->size;

        return $spiBinaryFile;
    }
}
