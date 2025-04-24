<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use Closure;
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
 * Abstract test case for persistence cache implementations.
 *
 * @phpstan-type TAdditionalCalls list<array{string, class-string, string, mixed}>
 */
abstract class AbstractBaseHandlerTestCase extends TestCase
{
    protected TransactionalInMemoryCacheAdapter & MockObject $cacheMock;

    protected Handler & MockObject $persistenceHandlerMock;

    protected CacheHandler $persistenceCacheHandler;

    protected PersistenceLogger & MockObject $loggerMock;

    protected InMemoryCache & MockObject $inMemoryMock;

    protected Closure $cacheItemsClosure;

    protected CacheIdentifierGeneratorInterface & MockObject $cacheIdentifierGeneratorMock;

    protected CacheIdentifierSanitizer $cacheIdentifierSanitizer;

    protected LocationPathConverter $locationPathConverter;

    protected CacheIndicesValidatorInterface & MockObject $cacheIndicesValidator;

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

        $this->cacheItemsClosure = Closure::bind(
            static function ($key, $value, $isHit, $defaultLifetime = 0): CacheItem {
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
     * @param mixed $value If null, the cache item will be assumed to be a cache miss here.
     */
    final protected function getCacheItem(string $key, mixed $value = null, int $defaultLifetime = 0): CacheItem
    {
        $cacheItemsClosure = $this->cacheItemsClosure;

        return $cacheItemsClosure($key, $value, (bool)$value, $defaultLifetime);
    }

    /**
     * @phpstan-return array{
     *     0: \Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter & \PHPUnit\Framework\MockObject\MockObject,
     *     1: \Ibexa\Contracts\Core\Persistence\Handler & \PHPUnit\Framework\MockObject\MockObject,
     *     2: \Ibexa\Core\Persistence\Cache\PersistenceLogger & \PHPUnit\Framework\MockObject\MockObject,
     *     3: \Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface & \PHPUnit\Framework\MockObject\MockObject,
     *     4: \Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierSanitizer,
     *     5: \Ibexa\Core\Persistence\Cache\LocationPathConverter
     * }
     */
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

    /**
     * @phpstan-return array{
     *     0: \Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter & \PHPUnit\Framework\MockObject\MockObject,
     *     1: \Ibexa\Core\Persistence\Cache\PersistenceLogger & \PHPUnit\Framework\MockObject\MockObject,
     *     2: \Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache & \PHPUnit\Framework\MockObject\MockObject,
     *     3: \Ibexa\Contracts\Core\Persistence\Handler & \PHPUnit\Framework\MockObject\MockObject,
     *     4: \Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface & \PHPUnit\Framework\MockObject\MockObject,
     *     5: \Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierSanitizer,
     *     6: \Ibexa\Core\Persistence\Cache\LocationPathConverter,
     *     7: \Ibexa\Core\Persistence\Cache\CacheIndicesValidatorInterface & \PHPUnit\Framework\MockObject\MockObject
     * }
     */
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
