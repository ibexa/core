<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy;

use Doctrine\DBAL\Connection;
use Exception;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler;
use Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\MemoryCachingHandler;
use Ibexa\Core\Persistence\Legacy\TransactionHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransactionHandler::class)]
class TransactionHandlerTest extends TestCase
{
    /**
     * Transaction handler to test.
     *
     * @var TransactionHandler
     */
    protected $transactionHandler;

    /** @var Connection|MockObject */
    protected $connectionMock;

    /** @var Handler|MockObject */
    protected $contentTypeHandlerMock;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Language\Handler|MockObject */
    protected $languageHandlerMock;

    public function testBeginTransaction(): void
    {
        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects($this->once())
            ->method('beginTransaction');
        $this->getContentTypeHandlerMock()
            ->expects($this->never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects($this->never())
            ->method(self::anything());

        $handler->beginTransaction();
    }

    public function testCommit(): void
    {
        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects($this->once())
            ->method('commit');
        $this->getContentTypeHandlerMock()
            ->expects($this->never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects($this->never())
            ->method(self::anything());

        $handler->commit();
    }

    public function testCommitException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test');

        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects($this->once())
            ->method('commit')
            ->will($this->throwException(new Exception('test')));
        $this->getContentTypeHandlerMock()
            ->expects($this->never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects($this->never())
            ->method(self::anything());

        $handler->commit();
    }

    public function testRollback(): void
    {
        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects($this->once())
            ->method('rollback');
        $this->getContentTypeHandlerMock()
            ->expects($this->once())
            ->method('clearCache');
        $this->getLanguageHandlerMock()
            ->expects($this->once())
            ->method('clearCache');

        $handler->rollback();
    }

    public function testRollbackException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test');

        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects($this->once())
            ->method('rollback')
            ->will($this->throwException(new Exception('test')));
        $this->getContentTypeHandlerMock()
            ->expects($this->never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects($this->never())
            ->method(self::anything());

        $handler->rollback();
    }

    /**
     * Returns a mock object for the Content Gateway.
     *
     * @return TransactionHandler
     */
    protected function getTransactionHandler()
    {
        if (!isset($this->transactionHandler)) {
            $this->transactionHandler = new TransactionHandler(
                $this->getConnectionMock(),
                $this->getContentTypeHandlerMock(),
                $this->getLanguageHandlerMock()
            );
        }

        return $this->transactionHandler;
    }

    /**
     * @return Connection|MockObject
     */
    protected function getConnectionMock(): Connection
    {
        if (!isset($this->connectionMock)) {
            $this->connectionMock = $this->createMock(Connection::class);
        }

        return $this->connectionMock;
    }

    /**
     * Returns a mock object for the content type handler.
     *
     * @return MemoryCachingHandler|MockObject
     */
    protected function getContentTypeHandlerMock()
    {
        if (!isset($this->contentTypeHandlerMock)) {
            $this->contentTypeHandlerMock = $this->createMock(MemoryCachingHandler::class);
        }

        return $this->contentTypeHandlerMock;
    }

    /**
     * Returns a mock object for the Content Language Gateway.
     *
     * @return CachingHandler|MockObject
     */
    protected function getLanguageHandlerMock()
    {
        if (!isset($this->languageHandlerMock)) {
            $this->languageHandlerMock = $this->createMock(CachingHandler::class);
        }

        return $this->languageHandlerMock;
    }
}
