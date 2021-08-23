<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter;
use Ibexa\Core\Persistence\Cache\Handler as CacheHandler;
use Ibexa\Core\Persistence\Cache\SectionHandler as CacheSectionHandler;
use Ibexa\Core\Persistence\Cache\LocationHandler as CacheLocationHandler;
use Ibexa\Core\Persistence\Cache\ContentHandler as CacheContentHandler;
use Ibexa\Core\Persistence\Cache\ContentLanguageHandler as CacheContentLanguageHandler;
use Ibexa\Core\Persistence\Cache\ContentTypeHandler as CacheContentTypeHandler;
use Ibexa\Core\Persistence\Cache\UserHandler as CacheUserHandler;
use Ibexa\Core\Persistence\Cache\TransactionHandler as CacheTransactionHandler;
use Ibexa\Core\Persistence\Cache\TrashHandler as CacheTrashHandler;
use Ibexa\Core\Persistence\Cache\UrlAliasHandler as CacheUrlAliasHandler;
use Ibexa\Core\Persistence\Cache\ObjectStateHandler as CacheObjectStateHandler;
use Ibexa\Core\Persistence\Cache\URLHandler as CacheUrlHandler;
use Ibexa\Core\Persistence\Cache\BookmarkHandler as CacheBookmarkHandler;
use Ibexa\Core\Persistence\Cache\NotificationHandler as CacheNotificationHandler;
use Ibexa\Core\Persistence\Cache\UserPreferenceHandler as CacheUserPreferenceHandler;
use Ibexa\Core\Persistence\Cache\UrlWildcardHandler as CacheUrlWildcardHandler;
use Ibexa\Core\Persistence\Cache\SettingHandler as CacheSettingHandler;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;
use Ibexa\Core\Persistence\Cache\PersistenceLogger;
use Ibexa\Contracts\Core\Persistence\Handler;
use Symfony\Component\Cache\CacheItem;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test case for spi cache impl.
 */
abstract class AbstractBaseHandlerTest extends TestCase
{
    /** @var \eZ\Publish\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheMock;

    /** @var \eZ\Publish\SPI\Persistence\Handler|\PHPUnit\Framework\MockObject\MockObject */
    protected $persistenceHandlerMock;

    /** @var \eZ\Publish\Core\Persistence\Cache\Handler */
    protected $persistenceCacheHandler;

    /** @var \eZ\Publish\Core\Persistence\Cache\PersistenceLogger|\PHPUnit\Framework\MockObject\MockObject */
    protected $loggerMock;

    /** @var \eZ\Publish\Core\Persistence\Cache\InMemory\InMemoryCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $inMemoryMock;

    /** @var \Closure */
    protected $cacheItemsClosure;

    /**
     * Setup the HandlerTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->persistenceHandlerMock = $this->createMock(Handler::class);
        $this->cacheMock = $this->createMock(TransactionalInMemoryCacheAdapter::class);
        $this->loggerMock = $this->createMock(PersistenceLogger::class);
        $this->inMemoryMock = $this->createMock(InMemoryCache::class);

        $this->persistenceCacheHandler = new CacheHandler(
            $this->persistenceHandlerMock,
            new CacheSectionHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            new CacheLocationHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheContentHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheContentLanguageHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheContentTypeHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheUserHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheTransactionHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheTrashHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            new CacheUrlAliasHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheObjectStateHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheUrlHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            new CacheBookmarkHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            new CacheNotificationHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            new CacheUserPreferenceHandler($this->cacheMock, $this->loggerMock, $this->inMemoryMock, $this->persistenceHandlerMock),
            new CacheUrlWildcardHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            new CacheSettingHandler($this->cacheMock, $this->persistenceHandlerMock, $this->loggerMock),
            $this->loggerMock
        );

        $this->cacheItemsClosure = \Closure::bind(
            static function ($key, $value, $isHit, $defaultLifetime = 0) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $value;
                $item->isHit = $isHit;
                $item->defaultLifetime = $defaultLifetime;
                $item->isTaggable = true;

                return $item;
            },
            null,
            CacheItem::class
        );
    }

    /**
     * Tear down test (properties).
     */
    protected function tearDown(): void
    {
        unset($this->cacheMock);
        unset($this->persistenceHandlerMock);
        unset($this->persistenceCacheHandler);
        unset($this->loggerMock);
        unset($this->cacheItemsClosure);
        unset($this->inMemoryMock);
        parent::tearDown();
    }

    /**
     * @param $key
     * @param null $value If null the cache item will be assumed to be a cache miss here.
     * @param int $defaultLifetime
     *
     * @return CacheItem
     */
    final protected function getCacheItem($key, $value = null, $defaultLifetime = 0)
    {
        $cacheItemsClosure = $this->cacheItemsClosure;

        return $cacheItemsClosure($key, $value, (bool)$value, $defaultLifetime);
    }
}

class_alias(AbstractBaseHandlerTest::class, 'eZ\Publish\Core\Persistence\Cache\Tests\AbstractBaseHandlerTest');
