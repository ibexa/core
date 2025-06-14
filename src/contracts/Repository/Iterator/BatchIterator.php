<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator;

use Iterator;

final class BatchIterator implements Iterator
{
    public const int DEFAULT_BATCH_SIZE = 25;

    private BatchIteratorAdapter $adapter;

    private ?Iterator $innerIterator = null;

    private int $batchSize;

    private int $position;

    public function __construct(
        BatchIteratorAdapter $adapter,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->adapter = $adapter;
        $this->batchSize = $batchSize;
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current(): mixed
    {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        return $this->innerIterator->current();
    }

    public function next(): void
    {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        ++$this->position;
        $this->innerIterator->next();
        if (!$this->innerIterator->valid() && ($this->position % $this->batchSize) === 0) {
            $this->innerIterator = $this->fetch();
        }
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        return $this->innerIterator->valid();
    }

    public function rewind(): void
    {
        $this->initialize();
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    private function initialize(): void
    {
        $this->position = 0;
        $this->innerIterator = $this->fetch();
    }

    private function isInitialized(): bool
    {
        return isset($this->innerIterator);
    }

    private function fetch(): Iterator
    {
        return $this->adapter->fetch($this->position, $this->batchSize);
    }
}
