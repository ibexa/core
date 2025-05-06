<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\Migration\FileLister\FileRowReader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Ibexa\Bundle\IO\Migration\FileLister\FileRowReaderInterface;
use LogicException;

abstract class LegacyStorageFileRowReader implements FileRowReaderInterface
{
    private Connection $connection;

    private ?Result $result;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->result = null;
    }

    final public function init()
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('filename', 'mime_type')
            ->from($this->getStorageTable());
        $this->result = $selectQuery->executeQuery();
    }

    /**
     * Returns the table name to store data in.
     *
     * @return string
     */
    abstract protected function getStorageTable();

    final public function getRow()
    {
        if (null === $this->result) {
            throw new LogicException('Uninitialized reader. You must call init() before getRow()');
        }

        $row = $this->result->fetchAssociative();

        return false !== $row ? $this->prependMimeToPath($row['filename'], $row['mime_type']) : null;
    }

    final public function getCount()
    {
        if (null === $this->result) {
            throw new LogicException('Uninitialized reader. You must call init() before getCount()');
        }

        return $this->result->rowCount();
    }

    /**
     * Prepends $path with the first part of the given $mimeType.
     *
     * @param string $path
     * @param string $mimeType
     *
     * @return string
     */
    private function prependMimeToPath($path, $mimeType): string
    {
        return substr($mimeType, 0, strpos($mimeType, '/')) . '/' . $path;
    }
}
