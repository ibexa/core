<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\TransactionHandler;

/**
 * @covers \Ibexa\Core\Persistence\Cache\TransactionHandler
 */
class TransactionHandlerTest extends AbstractCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'transactionHandler';
    }

    public function getHandlerClassName(): string
    {
        return TransactionHandler::class;
    }

    public function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array $arguments, array? $cacheTagGeneratingArguments, array? $cacheKeyGeneratingArguments, array? $tags, string? $key
        return [
            ['beginTransaction', []],
            ['commit', []],
        ];
    }

    public function providerForCachedLoadMethodsHit(): array
    {
        // string $method, array $arguments, array? $cacheIdentifierGeneratorArguments, array? $cacheIdentifierGeneratorResults, string $key, mixed? $data
        return [
        ];
    }

    public function providerForCachedLoadMethodsMiss(): array
    {
        // string $method, array $arguments, array? $cacheIdentifierGeneratorArguments, array? $cacheIdentifierGeneratorResults, string $key, mixed? $data
        return [
        ];
    }

    public function testRollback()
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('logCall');

        $this->cacheMock
            ->expects(self::never())
            ->method('clear');

        $this->cacheMock
            ->expects(self::once())
            ->method('rollbackTransaction');

        $innerHandlerMock = $this->createMock(TransactionHandler::class);
        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('transactionHandler')
            ->willReturn($innerHandlerMock);

        $innerHandlerMock
            ->expects(self::once())
            ->method('rollback');

        $handler = $this->persistenceCacheHandler->transactionHandler();
        $handler->rollback();
    }

    public function testCommitStopsCacheTransaction()
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('logCall');

        $this->cacheMock
            ->expects(self::once())
            ->method('commitTransaction');

        $innerHandlerMock = $this->createMock(TransactionHandler::class);
        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('transactionHandler')
            ->willReturn($innerHandlerMock);

        $innerHandlerMock
            ->expects(self::once())
            ->method('commit');

        $handler = $this->persistenceCacheHandler->transactionHandler();
        $handler->commit();
    }

    public function testBeginTransactionStartsCacheTransaction()
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('logCall');

        $this->cacheMock
            ->expects(self::once())
            ->method('beginTransaction');

        $innerHandlerMock = $this->createMock(TransactionHandler::class);
        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('transactionHandler')
            ->willReturn($innerHandlerMock);

        $innerHandlerMock
            ->expects(self::once())
            ->method('beginTransaction');

        $handler = $this->persistenceCacheHandler->transactionHandler();
        $handler->beginTransaction();
    }
}
