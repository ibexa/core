<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Collection;

use Ibexa\Contracts\Core\Collection\MutableArrayMap;

final class MutableArrayMapTest extends ArrayMapTest
{
    public function testSetWithExistingKey(): void
    {
        $map = $this->createCollection(['key' => 'value']);
        self::assertEquals('value', $map->get('key'));
        $map->set('key', 'updated_value');
        self::assertEquals('updated_value', $map->get('key'));
    }

    public function testSetWithNewKey(): void
    {
        $map = $this->createCollection([]);
        self::assertFalse($map->has('key'));
        $map->set('key', 'new_value');
        self::assertEquals('new_value', $map->get('key'));
    }

    public function testUnsetExistingKey(): void
    {
        $map = $this->createCollection(['key' => 'value']);
        self::assertTrue($map->has('key'));
        $map->unset('key');
        self::assertFalse($map->has('key'));
    }

    public function testUnsetNonExistingKey(): void
    {
        $map = $this->createCollection([]);
        self::assertFalse($map->has('non-existing'));
        $map->unset('non-existing');
        self::assertFalse($map->has('non-existing'));
    }

    public function testClear(): void
    {
        $map = $this->createCollection(self::EXAMPLE_DATA);
        self::assertFalse($map->isEmpty());
        $map->clear();
        self::assertTrue($map->isEmpty());
    }

    protected function createCollection(array $data): MutableArrayMap
    {
        return new MutableArrayMap($data);
    }
}
