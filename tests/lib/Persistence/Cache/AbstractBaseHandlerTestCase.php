<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter;
use Ibexa\Core\Persistence\Cache\BookmarkHandler as CacheBookmarkHandler;
use Ibexa\Core\Persistence\Cache\CacheIndicesValidatorInterface;
use Ibexa\Core\Persistence\Cache\ContentHandler as CacheContentHandler;
use Ibexa\Core\Persistence\Cache\ContentLanguageHandler as CacheContentLanguageHandler;
use Ibexa\Core\Persistence\Cache\ContentTypeHandler as CacheContentTypeHandler;
use Ibexa\Core\Persistence\Cache\Handler as CacheHandler;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierSanitizer;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;
use Ibexa\Core\Persistence\Cache\LocationHandler as CacheLocationHandler;
use Ibexa\Core\Persistence\Cache\LocationPathConverter;
use Ibexa\Core\Persistence\Cache\NotificationHandler as CacheNotificationHandler;
use Ibexa\Core\Persistence\Cache\ObjectStateHandler as CacheObjectStateHandler;
use Ibexa\Core\Persistence\Cache\PersistenceLogger;
use Ibexa\Core\Persistence\Cache\SectionHandler as CacheSectionHandler;
use Ibexa\Core\Persistence\Cache\SettingHandler as CacheSettingHandler;
use Ibexa\Core\Persistence\Cache\TransactionHandler as CacheTransactionHandler;
use Ibexa\Core\Persistence\Cache\TrashHandler as CacheTrashHandler;
use Ibexa\Core\Persistence\Cache\UrlAliasHandler as CacheUrlAliasHandler;
use Ibexa\Core\Persistence\Cache\URLHandler as CacheUrlHandler;
use Ibexa\Core\Persistence\Cache\UrlWildcardHandler as CacheUrlWildcardHandler;
use Ibexa\Core\Persistence\Cache\UserHandler as CacheUserHandler;
use Ibexa\Core\Persistence\Cache\UserPreferenceHandler as CacheUserPreferenceHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;

/**
 * Abstract test case for spi cache impl.
 */
abstract class AbstractBaseHandlerTestCase extends TestCase
{
    /** @var TransactionalInMemoryCacheAdapter|MockObject */
    protected $cacheMock;

    /** @var Handler|MockObject */
    protected $persistenceHandlerMock;

    /** @var CacheHandler */
    protected $persistenceCacheHandler;

    /** @var PersistenceLogger|MockObject */
    protected $loggerMock;

    /** @var InMemoryCache|MockObject */
    protected $inMemoryMock;

    /** @var \Closure */
    protected $cacheItemsClosure;

    /** @var CacheIdentifierGeneratorInterface|MockObject */
    protected $cacheIdentifierGeneratorMock;

    /** @var CacheIdentifierSanitizer */
    protected $cacheIdentifierSanitizer;

    /** @var LocationPathConverter */
    protected $locationPathConverter;

    /** @var CacheIndicesValidatorInterface */
    protected $cacheIndicesValidator;

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
        $this->cacheIdentifierGeneratorMock = $this->createMock(CacheIdentifierGeneratorInterface::class);
        $this->cacheIdentifierSanitizer = new CacheIdentifierSanitizer();
        $this->locationPathConverter = new LocationPathConverter();
        $this->cacheIndicesValidator = $this->createMock(CacheIndicesValidatorInterface::class);

        $cacheAbstractHandlerArguments = $this->provideAbstractCacheHandlerArguments();
        $cacheInMemoryHandlerArguments = $this->provideInMemoryCacheHandlerArguments();

        $this->persistenceCacheHandler = new CacheHandler(
            $this->persistenceHandlerMock,
            new CacheSectionHandler(...$cacheAbstractHandlerArguments),
            new CacheLocationHandler(...$cacheInMemoryHandlerArguments),
            new CacheContentHandler(...$cacheInMemoryHandlerArguments),
            new CacheContentLanguageHandler(...$cacheInMemoryHandlerArguments),
            new CacheContentTypeHandler(...$cacheInMemoryHandlerArguments),
            new CacheUserHandler(...$cacheInMemoryHandlerArguments),
            new CacheTransactionHandler(...$cacheInMemoryHandlerArguments),
            new CacheTrashHandler(...$cacheAbstractHandlerArguments),
            new CacheUrlAliasHandler(...$cacheInMemoryHandlerArguments),
            new CacheObjectStateHandler(...$cacheInMemoryHandlerArguments),
            new CacheUrlHandler(...$cacheAbstractHandlerArguments),
            new CacheBookmarkHandler(...$cacheAbstractHandlerArguments),
            new CacheNotificationHandler(...$cacheAbstractHandlerArguments),
            new CacheUserPreferenceHandler(...$cacheInMemoryHandlerArguments),
            new CacheUrlWildcardHandler(...$cacheAbstractHandlerArguments),
            new CacheSettingHandler(...$cacheInMemoryHandlerArguments),
            $this->loggerMock
        );

        $this->cacheItemsClosure = \Closure::bind(
            static function (
                $key,
                $value,
                $isHit,
                $defaultLifetime = 0
            ) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $value;
                $item->isHit = $isHit;
                $item->expiresAfter($defaultLifetime);
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
        unset(
            $this->cacheMock,
            $this->persistenceHandlerMock,
            $this->persistenceCacheHandler,
            $this->loggerMock,
            $this->cacheItemsClosure,
            $this->inMemoryMock,
            $this->cacheIdentifierGeneratorMock,
            $this->cacheIdentifierSanitizer,
            $this->locationPathConverter
        );

        parent::tearDown();
    }

    /**
     * @param $key
     * @param null $value If null the cache item will be assumed to be a cache miss here.
     * @param int $defaultLifetime
     *
     * @return CacheItem
     */
    final protected function getCacheItem(
        $key,
        $value = null,
        $defaultLifetime = 0
    ) {
        $cacheItemsClosure = $this->cacheItemsClosure;

        return $cacheItemsClosure($key, $value, (bool)$value, $defaultLifetime);
    }

    private function provideAbstractCacheHandlerArguments(): array
    {
        return [
            $this->cacheMock,
            $this->persistenceHandlerMock,
            $this->loggerMock,
            $this->cacheIdentifierGeneratorMock,
            $this->cacheIdentifierSanitizer,
            $this->locationPathConverter,
        ];
    }

    private function provideInMemoryCacheHandlerArguments(): array
    {
        return [
            $this->cacheMock,
            $this->loggerMock,
            $this->inMemoryMock,
            $this->persistenceHandlerMock,
            $this->cacheIdentifierGeneratorMock,
            $this->cacheIdentifierSanitizer,
            $this->locationPathConverter,
            $this->cacheIndicesValidator,
        ];
    }
}
