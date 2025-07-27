<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Collection;

use Ibexa\Contracts\Core\Collection\MutableArrayList;

final class MutableArrayListTest extends ArrayListTest
{
    public function testAppend(): void
    {
        /** @var \Ibexa\Contracts\Core\Collection\MutableArrayList $list */
        $list = $this->createEmptyCollection();
        $list->append('A');
        $list->append('B');
        $list->append('C');

        self::assertEquals(['A', 'B', 'C'], $list->toArray());
    }

    public function testPrepend(): void
    {
        /** @var \Ibexa\Contracts\Core\Collection\MutableArrayList $list */
        $list = $this->createEmptyCollection();
        $list->prepend('A');
        $list->prepend('B');
        $list->prepend('C');

        self::assertEquals(['C', 'B', 'A'], $list->toArray());
    }

    public function testRemove(): void
    {
        /** @var \Ibexa\Contracts\Core\Collection\MutableArrayList $list */
        $list = $this->createCollectionWithExampleData();
        $list->remove('B');

        self::assertEquals(['A', 'C'], $list->toArray());
    }

    public function testClear(): void
    {
        /** @var \Ibexa\Contracts\Core\Collection\MutableArrayList $list */
        $list = $this->createCollectionWithExampleData();
        self::assertFalse($list->isEmpty());
        $list->clear();
        self::assertTrue($list->isEmpty());
    }

    protected function createCollection(array $data): MutableArrayList
    {
        return new MutableArrayList($data);
    }
}
