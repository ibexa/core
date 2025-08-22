<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache\Adapter;

use Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * Test case for Adapter decorator.
 */
class InMemoryClearingProxyAdapterTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter */
    protected $cache;

    /** @var \Symfony\Component\Cache\Adapter\TagAwareAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $innerPool;

    /** @var \Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache|\PHPUnit\Framework\MockObject\MockObject */
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
            static function ($key, $value, $isHit, $defaultLifetime = 0, $tags = []) {
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

    public function testGetItem()
    {
        $item = $this->createCacheItem('first');

        $this->innerPool
            ->expects(self::once())
            ->method('getItem')
            ->with('first')
            ->willReturn($item);

        $this->inMemory->expects(self::never())->method(self::anything());

        $returnedItem = $this->cache->getItem('first');
        self::assertSame($item, $returnedItem);
    }

    public function testGetItems()
    {
        $items = [
            'first' => $this->createCacheItem('first'),
            'second' => $this->createCacheItem('second'),
        ];

        $this->innerPool
            ->expects(self::once())
            ->method('getItems')
            ->with(['first', 'second'])
            ->willReturn($items);

        $this->inMemory->expects(self::never())->method(self::anything());

        $returnedItems = $this->cache->getItems(['first', 'second']);
        self::assertSame($items, $returnedItems);
    }

    /**
     * Symfony uses generators with getItems() so we need to make sure we handle that.
     */
    public function testGetItemsWithGenerator()
    {
        $items = [
            'first' => $this->createCacheItem('first'),
            'second' => $this->createCacheItem('second'),
        ];

        $this->innerPool
            ->expects(self::once())
            ->method('getItems')
            ->with(['first', 'second'])
            ->willReturn($this->arrayAsGenerator($items));

        $this->inMemory->expects(self::never())->method(self::anything());

        $returnedItems = iterator_to_array($this->cache->getItems(['first', 'second']));
        self::assertSame($items, $returnedItems);
    }

    public function testHasItem()
    {
        $this->innerPool
            ->expects(self::once())
            ->method('hasItem')
            ->with('first')
            ->willReturn(true);

        $this->inMemory->expects(self::never())->method(self::anything());

        self::assertTrue($this->cache->hasItem('first'));
    }

    /**
     * @dataProvider providerForDelete
     */
    public function testDelete(string $method, $argument)
    {
        $this->innerPool
            ->expects(self::once())
            ->method($method)
            ->with($argument)
            ->willReturn(true);

        $this->inMemory
            ->expects(self::once())
            ->method('deleteMulti')
            ->with(is_array($argument) ? $argument : [$argument]);

        // invalidate it
        self::assertTrue($this->cache->$method($argument));
    }

    public function providerForDelete(): array
    {
        return [
            ['deleteItem', 'first'],
            ['deleteItems', ['first']],
            ['deleteItems', ['first', 'second']],
        ];
    }

    /**
     * Test for clear and invalidateTags as both expects a clear to in-memory as it on purpose does not track tags.
     *
     * @dataProvider providerForClearAndInvalidation
     */
    public function testClearAndInvalidation(string $method, $argument)
    {
        if ($argument) {
            $this->innerPool
                ->expects(self::once())
                ->method($method)
                ->with($argument)
                ->willReturn(true);
        } else {
            $this->innerPool
                ->expects(self::once())
                ->method($method)
                ->willReturn(true);
        }

        $this->inMemory
            ->expects(self::once())
            ->method('clear');

        // invalidate it
        self::assertTrue($this->cache->$method($argument));
    }

    public function providerForClearAndInvalidation(): array
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
     * @return \Symfony\Component\Cache\CacheItem
     */
    private function createCacheItem($key, $tags = [], $value = true)
    {
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
