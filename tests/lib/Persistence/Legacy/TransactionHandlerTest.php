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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\TransactionHandler
 */
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

    public function testBeginTransaction()
    {
        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects(self::once())
            ->method('beginTransaction');
        $this->getContentTypeHandlerMock()
            ->expects(self::never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects(self::never())
            ->method(self::anything());

        $handler->beginTransaction();
    }

    public function testCommit()
    {
        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects(self::once())
            ->method('commit');
        $this->getContentTypeHandlerMock()
            ->expects(self::never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects(self::never())
            ->method(self::anything());

        $handler->commit();
    }

    public function testCommitException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test');

        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects(self::once())
            ->method('commit')
            ->will(self::throwException(new Exception('test')));
        $this->getContentTypeHandlerMock()
            ->expects(self::never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects(self::never())
            ->method(self::anything());

        $handler->commit();
    }

    public function testRollback()
    {
        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects(self::once())
            ->method('rollback');
        $this->getContentTypeHandlerMock()
            ->expects(self::once())
            ->method('clearCache');
        $this->getLanguageHandlerMock()
            ->expects(self::once())
            ->method('clearCache');

        $handler->rollback();
    }

    public function testRollbackException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test');

        $handler = $this->getTransactionHandler();
        $this->getConnectionMock()
            ->expects(self::once())
            ->method('rollback')
            ->will(self::throwException(new Exception('test')));
        $this->getContentTypeHandlerMock()
            ->expects(self::never())
            ->method(self::anything());
        $this->getLanguageHandlerMock()
            ->expects(self::never())
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
