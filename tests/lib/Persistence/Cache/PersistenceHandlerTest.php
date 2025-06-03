<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence as SPIPersistence;
use Ibexa\Core\Persistence\Cache;

/**
 * Test case for Persistence\Cache\Handler.
 *
 * @covers \Ibexa\Core\Persistence\Cache\Handler
 */
class PersistenceHandlerTest extends AbstractBaseHandlerTestCase
{
    public function testHandler()
    {
        self::assertInstanceOf(SPIPersistence\Handler::class, $this->persistenceCacheHandler);
        self::assertInstanceOf(Cache\Handler::class, $this->persistenceCacheHandler);
    }

    public function testContentHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->contentHandler();
        self::assertInstanceOf(SPIPersistence\Content\Handler::class, $handler);
        self::assertInstanceOf(Cache\ContentHandler::class, $handler);
    }

    public function testLanguageHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->contentLanguageHandler();
        self::assertInstanceOf(SPIPersistence\Content\Language\Handler::class, $handler);
        self::assertInstanceOf(Cache\ContentLanguageHandler::class, $handler);
    }

    public function testContentTypeHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->contentTypeHandler();
        self::assertInstanceOf(SPIPersistence\Content\Type\Handler::class, $handler);
        self::assertInstanceOf(Cache\ContentTypeHandler::class, $handler);
    }

    public function testContentLocationHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->locationHandler();
        self::assertInstanceOf(SPIPersistence\Content\Location\Handler::class, $handler);
        self::assertInstanceOf(Cache\LocationHandler::class, $handler);
    }

    public function testTrashHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->trashHandler();
        self::assertInstanceOf(SPIPersistence\Content\Location\Trash\Handler::class, $handler);
        self::assertInstanceOf(Cache\TrashHandler::class, $handler);
    }

    public function testObjectStateHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->objectStateHandler();
        self::assertInstanceOf(SPIPersistence\Content\ObjectState\Handler::class, $handler);
        self::assertInstanceOf(Cache\ObjectStateHandler::class, $handler);
    }

    public function testSectionHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->sectionHandler();
        self::assertInstanceOf(SPIPersistence\Content\Section\Handler::class, $handler);
        self::assertInstanceOf(Cache\SectionHandler::class, $handler);
    }

    public function testUserHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->userHandler();
        self::assertInstanceOf(SPIPersistence\User\Handler::class, $handler);
        self::assertInstanceOf(Cache\UserHandler::class, $handler);
    }

    public function testUrlAliasHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->urlAliasHandler();
        self::assertInstanceOf(SPIPersistence\Content\UrlAlias\Handler::class, $handler);
        self::assertInstanceOf(Cache\UrlAliasHandler::class, $handler);
    }

    public function testUrlWildcardHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->urlWildcardHandler();
        self::assertInstanceOf(SPIPersistence\Content\UrlWildcard\Handler::class, $handler);
        self::assertInstanceOf(Cache\UrlWildcardHandler::class, $handler);
    }

    public function testTransactionHandler()
    {
        $this->loggerMock->expects(self::never())->method(self::anything());
        $handler = $this->persistenceCacheHandler->transactionHandler();
        self::assertInstanceOf(SPIPersistence\TransactionHandler::class, $handler);
        self::assertInstanceOf(Cache\TransactionHandler::class, $handler);
    }
}
