<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Collection;

use Ibexa\Contracts\Core\Collection\ArrayList;
use Ibexa\Contracts\Core\Exception\OutOfBoundsException;

/**
 * @template-extends \Ibexa\Tests\Core\Collection\AbstractCollectionTestCase<
 *     \Ibexa\Contracts\Core\Collection\ArrayList
 * >
 */
class ArrayListTest extends AbstractCollectionTestCase
{
    public function testFirst(): void
    {
        self::assertEquals('A', ($this->createCollection(['A', 'B', 'C']))->first());
    }

    public function testLast(): void
    {
        self::assertEquals('C', ($this->createCollection(['A', 'B', 'C']))->last());
    }

    public function testFirstThrowsOutOfBoundsException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Collection is empty');

        /** @var ArrayList $list */
        $list = $this->createEmptyCollection();
        $list->first();
    }

    public function testLastThrowsOutOfBoundsException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Collection is empty');

        /** @var ArrayList $list */
        $list = $this->createEmptyCollection();
        $list->last();
    }

    public function testContains(): void
    {
        $list = $this->createCollection(['a', 'b', 'c']);

        self::assertTrue($list->contains('a'));
        self::assertFalse($list->contains('z'));
    }

    public function testMap(): void
    {
        $list = $this->createCollection(['a', 'b', 'c']);

        self::assertEquals(
            $this->createCollection(['A', 'B', 'C']),
            $list->map(static fn (string $value) => strtoupper($value))
        );
    }

    public function testFilter(): void
    {
        $list = $this->createCollection(['A', '7', 'B', 'C', '9', '10']);

        self::assertEquals(
            $this->createCollection(['7', '9', '10']),
            $list->filter(static fn (string $item) => ctype_digit($item))
        );
    }

    /**
     * @return string[]
     */
    protected function getExampleData(): array
    {
        return ['A', 'B', 'C'];
    }

    protected function createCollection(array $data): ArrayList
    {
        return new ArrayList($data);
    }
}
