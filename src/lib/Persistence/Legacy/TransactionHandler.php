<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy;

use Doctrine\DBAL\Connection;
use Exception;
use Ibexa\Contracts\Core\Persistence\TransactionHandler as TransactionHandlerInterface;
use Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler;
use Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler as CachingLanguageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\MemoryCachingHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\MemoryCachingHandler as CachingContentTypeHandler;
use RuntimeException;

/**
 * The Transaction handler for Legacy Storage Engine.
 *
 * @since 5.3
 */
class TransactionHandler implements TransactionHandlerInterface
{
    protected Connection $connection;

    protected ?MemoryCachingHandler $contentTypeHandler;

    protected ?CachingHandler $languageHandler;

    public function __construct(
        Connection $connection,
        CachingContentTypeHandler $contentTypeHandler = null,
        CachingLanguageHandler $languageHandler = null
    ) {
        $this->connection = $connection;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->languageHandler = $languageHandler;
    }

    /**
     * Begin transaction.
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transaction.
     *
     * Commit transaction, or throw exceptions if no transactions has been started.
     *
     * @throws \RuntimeException If no transaction has been started
     */
    public function commit(): void
    {
        try {
            $this->connection->commit();
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Rollback transaction.
     *
     * Rollback transaction, or throw exceptions if no transactions has been started.
     *
     * @throws \RuntimeException If no transaction has been started
     */
    public function rollback(): void
    {
        try {
            $this->connection->rollback();

            // Clear all caches after rollback
            if ($this->contentTypeHandler instanceof CachingContentTypeHandler) {
                $this->contentTypeHandler->clearCache();
            }

            if ($this->languageHandler instanceof CachingLanguageHandler) {
                $this->languageHandler->clearCache();
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
