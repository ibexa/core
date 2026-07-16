<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Collection;

use Closure;
use Ibexa\Contracts\Core\Collection\CollectionInterface;
use Ibexa\Contracts\Core\Collection\StreamableInterface;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;

/**
 * @template TCollection of \Ibexa\Contracts\Core\Repository\Collection\CollectionInterfaces
 */
abstract class AbstractCollectionTestCase extends TestCase
{
    public function testIsEmptyReturnsTrue(): void
    {
        self::assertTrue($this->createEmptyCollection()->isEmpty());
    }

    public function testIsEmptyReturnsFalse(): void
    {
        self::assertFalse($this->createCollectionWithExampleData()->isEmpty());
    }

    public function testToArray(): void
    {
        self::assertEquals($this->getExampleData(), $this->createCollectionWithExampleData()->toArray());
    }

    public function testCount(): void
    {
        self::assertCount(
            count($this->getExampleData()),
            $this->createCollectionWithExampleData()
        );
    }

    public function testIsIterable(): void
    {
        $collection = $this->createCollectionWithExampleData();

        self::assertInstanceOf(IteratorAggregate::class, $collection);
        self::assertEquals($this->getExampleData(), iterator_to_array($collection->getIterator()));
    }

    public function testFilterEdgeCases(): void
    {
        $input = $this->createCollection($this->getExampleData());

        self::assertEquals(
            $this->createEmptyCollection(),
            $input->filter($this->getContradiction())
        );

        self::assertEquals(
            $this->createCollectionWithExampleData(),
            $input->filter($this->getTautology())
        );
    }

    public function testExistsEdgeCases(): void
    {
        $collection = $this->createCollectionWithExampleData();
        if (!($collection instanceof StreamableInterface)) {
            self::markTestSkipped(sprintf('%s collection is not streamable', get_class($collection)));
        }

        self::assertTrue($collection->exists($this->getTautology()));
        self::assertFalse($collection->exists($this->getContradiction()));
    }

    public function testForAllEdgeCases(): void
    {
        $collection = $this->createCollectionWithExampleData();
        if (!($collection instanceof StreamableInterface)) {
            self::markTestSkipped(sprintf('%s collection is not streamable', get_class($collection)));
        }

        self::assertTrue($collection->forAll($this->getTautology()));
        self::assertFalse($collection->forAll($this->getContradiction()));
    }

    abstract protected function getExampleData(): array;

    /**
     * @return TCollection
     */
    abstract protected function createCollection(array $data): CollectionInterface;

    /**
     * @return TCollection
     */
    protected function createEmptyCollection(): CollectionInterface
    {
        return $this->createCollection([]);
    }

    /**
     * @return TCollection
     */
    protected function createCollectionWithExampleData(): CollectionInterface
    {
        return $this->createCollection($this->getExampleData());
    }

    /**
     * Returns a predicate which is always true.
     */
    protected function getTautology(): Closure
    {
        return static fn (): bool => true;
    }

    /**
     * Returns a predicate which is always false.
     */
    protected function getContradiction(): Closure
    {
        return static fn (): bool => false;
    }
}
