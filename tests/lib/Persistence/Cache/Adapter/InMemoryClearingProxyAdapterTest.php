<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache\Adapter;

use Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * Test case for Adapter decorator.
 */
class InMemoryClearingProxyAdapterTest extends TestCase
{
    /** @var TransactionalInMemoryCacheAdapter */
    protected $cache;

    /** @var TagAwareAdapterInterface|MockObject */
    protected $innerPool;

    /** @var InMemoryCache|MockObject */
    protected $inMemory;

    /** @var \Closure */
    private $cacheItemsClosure;

    /**
     * Setup the HandlerTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerPool = $this->createMock(TagAwareAdapterInterface::class);
        $this->inMemory = $this->createMock(InMemoryCache::class);

        $this->cache = new TransactionalInMemoryCacheAdapter(
            $this->innerPool,
            [$this->inMemory]
        );

        $this->cacheItemsClosure = \Closure::bind(
            static function (
                $key,
                $value,
                $isHit,
                $defaultLifetime = 0,
                $tags = []
            ): CacheItem {
                $item = new CacheItem();
                $item->isTaggable = true;
                $item->key = $key;
                $item->value = $value;
                $item->isHit = $isHit;
                $item->tag($tags);
                $item->expiresAfter($defaultLifetime);

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
        unset($this->cache);
        unset($this->innerPool);
        unset($this->inMemory);

        parent::tearDown();
    }

    public function testGetItem(): void
    {
        $item = $this->createCacheItem('first');

        $this->innerPool
            ->expects($this->once())
            ->method('getItem')
            ->with('first')
            ->willReturn($item);

        $this->inMemory->expects($this->never())->method(self::anything());

        $returnedItem = $this->cache->getItem('first');
        self::assertSame($item, $returnedItem);
    }

    public function testGetItems(): void
    {
        $items = [
            'first' => $this->createCacheItem('first'),
            'second' => $this->createCacheItem('second'),
        ];

        $this->innerPool
            ->expects($this->once())
            ->method('getItems')
            ->with(['first', 'second'])
            ->willReturn($items);

        $this->inMemory->expects($this->never())->method(self::anything());

        $returnedItems = $this->cache->getItems(['first', 'second']);
        self::assertSame($items, $returnedItems);
    }

    /**
     * Symfony uses generators with getItems() so we need to make sure we handle that.
     */
    public function testGetItemsWithGenerator(): void
    {
        $items = [
            'first' => $this->createCacheItem('first'),
            'second' => $this->createCacheItem('second'),
        ];

        $this->innerPool
            ->expects($this->once())
            ->method('getItems')
            ->with(['first', 'second'])
            ->willReturn($this->arrayAsGenerator($items));

        $this->inMemory->expects($this->never())->method(self::anything());

        $returnedItems = iterator_to_array($this->cache->getItems(['first', 'second']));
        self::assertSame($items, $returnedItems);
    }

    public function testHasItem(): void
    {
        $this->innerPool
            ->expects($this->once())
            ->method('hasItem')
            ->with('first')
            ->willReturn(true);

        $this->inMemory->expects($this->never())->method(self::anything());

        self::assertTrue($this->cache->hasItem('first'));
    }

    #[DataProvider('providerForDelete')]
    public function testDelete(
        string $method,
        $argument
    ): void {
        $this->innerPool
            ->expects($this->once())
            ->method($method)
            ->with($argument)
            ->willReturn(true);

        $this->inMemory
            ->expects($this->once())
            ->method('deleteMulti')
            ->with(is_array($argument) ? $argument : [$argument]);

        // invalidate it
        self::assertTrue($this->cache->$method($argument));
    }

    public static function providerForDelete(): array
    {
        return [
            ['deleteItem', 'first'],
            ['deleteItems', ['first']],
            ['deleteItems', ['first', 'second']],
        ];
    }

    /**
     * Test for clear and invalidateTags as both expects a clear to in-memory as it on purpose does not track tags.
     */
    #[DataProvider('providerForClearAndInvalidation')]
    public function testClearAndInvalidation(
        string $method,
        $argument
    ): void {
        if ($argument) {
            $this->innerPool
                ->expects($this->once())
                ->method($method)
                ->with($argument)
                ->willReturn(true);
        } else {
            $this->innerPool
                ->expects($this->once())
                ->method($method)
                ->willReturn(true);
        }

        $this->inMemory
            ->expects($this->once())
            ->method('clear');

        // invalidate it
        self::assertTrue($this->cache->$method($argument));
    }

    public static function providerForClearAndInvalidation(): array
    {
        return [
            ['invalidateTags', ['my_tag']],
            ['invalidateTags', ['my_tag', 'another_tag']],
            ['clear', ''],
        ];
    }

    /**
     * @param string $key
     * @param array $tags Optional.
     * @param mixed $value Optional, if value evaluates to false the cache item will be assumed to be a cache miss here.
     *
     * @return CacheItem
     */
    private function createCacheItem(
        $key,
        $tags = [],
        $value = true
    ) {
        $cacheItemsClosure = $this->cacheItemsClosure;

        return $cacheItemsClosure($key, $value, (bool) $value, 0, $tags);
    }

    /**
     * @param array $array
     *
     * @return \Generator
     */
    private function arrayAsGenerator(array $array)
    {
        foreach ($array as $key => $item) {
            yield $key => $item;
        }
    }
}
