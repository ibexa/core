<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Cache\Adapter;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Internal proxy adapter invalidating our isolated in-memory cache, and defer shared pool changes during transactions.
 *
 * @internal For type hinting inside {@see \Ibexa\Core\Persistence\Cache\}. For external, type hint on
 * {@see \Symfony\Component\Cache\Adapter\TagAwareAdapterInterface}.
 */
class TransactionalInMemoryCacheAdapter implements TransactionAwareAdapterInterface
{
    protected TagAwareAdapterInterface $sharedPool;

    /** @var iterable<\Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache> */
    private iterable $inMemoryPools;

    /** @var int */
    protected int $transactionDepth;

    /** @var array<string, true> To be unique and simplify lookup hash key is cache tag, value is only true value */
    protected array $deferredTagsInvalidation;

    /** @var array<string, true> To be unique and simplify lookup hash key is cache key, value is only true value */
    protected array $deferredItemsDeletion;

    /** @var \Closure Callback for use by {@see markItemsAsDeferredMissIfNeeded()} when items are misses by deferred action */
    protected \Closure $setCacheItemAsMiss;

    /**
     * @param \Symfony\Component\Cache\Adapter\TagAwareAdapterInterface $sharedPool
     * @param \Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache[] $inMemoryPools
     * @param int $transactionDepth
     * @param array<string, true> $deferredTagsInvalidation
     * @param array<string, true> $deferredItemsDeletion
     */
    public function __construct(
        TagAwareAdapterInterface $sharedPool,
        iterable $inMemoryPools,
        int $transactionDepth = 0,
        array $deferredTagsInvalidation = [],
        array $deferredItemsDeletion = []
    ) {
        $this->sharedPool = $sharedPool;
        $this->inMemoryPools = $inMemoryPools;
        $this->transactionDepth = $transactionDepth;
        $this->deferredTagsInvalidation = empty($deferredTagsInvalidation) ? [] : \array_fill_keys($deferredTagsInvalidation, true);
        $this->deferredItemsDeletion = empty($deferredItemsDeletion) ? [] : \array_fill_keys($deferredItemsDeletion, true);
        // To modify protected $isHit when items are a "miss" based on deferred delete/invalidation during transactions
        $this->setCacheItemAsMiss = \Closure::bind(
            static function (CacheItem $item) {
                // ... Might not work for anything but new items
                $item->isHit = false;
            },
            null,
            CacheItem::class
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Exception\InvalidArgumentException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getItem($key): CacheItem
    {
        $item = current(
            $this->markItemsAsDeferredMissIfNeeded(
                [$key => $this->sharedPool->getItem($key)]
            )
        );
        if ($item === false) {
            throw new InvalidArgumentException('$key', "Unknown cache key: $key");
        }

        return $item;
    }

    public function getItems(array $keys = []): iterable
    {
        return $this->markItemsAsDeferredMissIfNeeded(
            $this->sharedPool->getItems($keys)
        );
    }

    public function hasItem(string $key): bool
    {
        if (isset($this->deferredItemsDeletion[$key])) {
            return false;
        }

        return $this->sharedPool->hasItem($key);
    }

    public function deleteItem(string $key): bool
    {
        foreach ($this->inMemoryPools as $inMemory) {
            $inMemory->deleteMulti([$key]);
        }

        if ($this->transactionDepth > 0) {
            $this->deferredItemsDeletion[$key] = true;

            return true;
        }

        return $this->sharedPool->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($this->inMemoryPools as $inMemory) {
            $inMemory->deleteMulti($keys);
        }

        if ($this->transactionDepth > 0) {
            $this->deferredItemsDeletion += \array_fill_keys($keys, true);

            return true;
        }

        return $this->sharedPool->deleteItems($keys);
    }

    public function invalidateTags(array $tags): bool
    {
        // No tracking of tags in in-memory, as it's anyway meant to only optimize for reads (GETs) and not writes.
        $this->clearInMemoryPools();

        if ($this->transactionDepth > 0) {
            $this->deferredTagsInvalidation += \array_fill_keys($tags, true);

            return true;
        }

        return $this->sharedPool->invalidateTags($tags);
    }

    public function clear(string $prefix = ''): bool
    {
        $this->clearInMemoryPools();

        // @todo Should we trow RunTime error or add support deferring full cache clearing?
        $this->transactionDepth = 0;
        $this->deferredItemsDeletion = [];
        $this->deferredTagsInvalidation = [];

        return $this->sharedPool->clear($prefix);
    }

    public function save(CacheItemInterface $item): bool
    {
        if ($this->transactionDepth > 0) {
            $this->deferredItemsDeletion[$item->getKey()] = true;

            return true;
        }

        return $this->sharedPool->save($item);
    }

    public function beginTransaction(): void
    {
        ++$this->transactionDepth;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function commitTransaction(): void
    {
        if ($this->transactionDepth === 0) {
            // ignore, might have been a previous rollback
            return;
        }

        --$this->transactionDepth;

        // Once we reach 0 transaction count, sent out deferred deletes/invalidations to shared pool
        if ($this->transactionDepth === 0) {
            if (!empty($this->deferredItemsDeletion)) {
                $this->sharedPool->deleteItems(\array_keys($this->deferredItemsDeletion));
                $this->deferredItemsDeletion = [];
            }

            if (!empty($this->deferredTagsInvalidation)) {
                $this->sharedPool->invalidateTags(\array_keys($this->deferredTagsInvalidation));
                $this->deferredTagsInvalidation = [];
            }
        }
    }

    public function rollbackTransaction(): void
    {
        $this->transactionDepth = 0;
        $this->deferredItemsDeletion = [];
        $this->deferredTagsInvalidation = [];

        $this->clearInMemoryPools();
    }

    /**
     * Symfony cache feature for deferring saves, not used by Ibexa & not related to transaction handling here.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->sharedPool->saveDeferred($item);
    }

    /**
     * Symfony cache feature for committing deferred saves, not used by Ibexa & not related to transaction handling here.
     */
    public function commit(): bool
    {
        return $this->sharedPool->commit();
    }

    /**
     * For use by getItem(s) to mark items as a miss if it's going to be cleared on transaction commit.
     *
     * @param iterable<string, \Symfony\Component\Cache\CacheItem> $items
     *
     * @return iterable<string, \Symfony\Component\Cache\CacheItem>
     */
    private function markItemsAsDeferredMissIfNeeded(iterable $items): iterable
    {
        if ($this->transactionDepth === 0) {
            return $items;
        }

        // In case of $items being generator we map items over to new array as it can't be iterated several times
        $iteratedItems = [];
        $fnSetCacheItemAsMiss = $this->setCacheItemAsMiss;
        foreach ($items as $key => $item) {
            $iteratedItems[$key] = $item;

            if (!$item->isHit()) {
                continue;
            }

            if ($this->itemIsDeferredMiss($item)) {
                $fnSetCacheItemAsMiss($item);
            }
        }

        return $iteratedItems;
    }

    private function itemIsDeferredMiss(CacheItem $item): bool
    {
        if (isset($this->deferredItemsDeletion[$item->getKey()])) {
            return true;
        }

        foreach ($item->getMetadata()[ItemInterface::METADATA_TAGS] as $tag) {
            if (isset($this->deferredTagsInvalidation[$tag])) {
                return true;
            }
        }

        return false;
    }

    private function clearInMemoryPools(): void
    {
        foreach ($this->inMemoryPools as $inMemory) {
            $inMemory->clear();
        }
    }
}
