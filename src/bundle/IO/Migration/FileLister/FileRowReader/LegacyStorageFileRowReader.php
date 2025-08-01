<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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

    final public function init(): void
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('filename', 'mime_type')
            ->from($this->getStorageTable());
        $this->result = $selectQuery->executeQuery();
    }

    /**
     * Returns the table name to store data in.
     */
    abstract protected function getStorageTable(): string;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    final public function getRow(): ?string
    {
        if (null === $this->result) {
            throw new LogicException('Uninitialized reader. You must call init() before getRow()');
        }

        $row = $this->result->fetchAssociative();

        return false !== $row ? $this->prependMimeToPath($row['filename'], $row['mime_type']) : null;
    }

    final public function getCount(): int
    {
        if (null === $this->result) {
            throw new LogicException('Uninitialized reader. You must call init() before getCount()');
        }

        /** @var int<0, max> */
        return $this->result->rowCount();
    }

    /**
     * Prepends $path with the first part of the given $mimeType.
     */
    private function prependMimeToPath(string $path, string $mimeType): string
    {
        return substr($mimeType, 0, strpos($mimeType, '/')) . '/' . $path;
    }
}
