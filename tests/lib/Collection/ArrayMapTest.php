<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Collection;

use Ibexa\Contracts\Core\Collection\ArrayMap;
use Ibexa\Contracts\Core\Collection\MapInterface;
use Ibexa\Contracts\Core\Collection\StreamableInterface;
use Ibexa\Contracts\Core\Exception\OutOfBoundsException;

/**
 * @template-extends \Ibexa\Tests\Core\Collection\AbstractCollectionTestCase<
 *     \Ibexa\Contracts\Core\Collection\ArrayMap
 * >
 */
class ArrayMapTest extends AbstractCollectionTestCase
{
    protected const EXAMPLE_DATA = [
        'A' => 'foo',
        'B' => 'bar',
        'C' => 'baz',
    ];

    public function testGet(): void
    {
        $map = $this->createCollection(self::EXAMPLE_DATA);

        self::assertEquals('foo', $map->get('A'));
        self::assertEquals('bar', $map->get('B'));
        self::assertEquals('baz', $map->get('C'));
    }

    public function testGetThrowsOutOfBoundsExceptionForNonExistingKey(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage("Collection does not contain element with key 'non-exiting'");

        /** @var ArrayMap $map */
        $map = $this->createEmptyCollection();
        $map->get('non-exiting');
    }

    public function testHas(): void
    {
        $map = $this->createCollection([
            'existing' => 'value',
        ]);

        self::assertTrue($map->has('existing'));
        self::assertFalse($map->has('non-existing'));
    }

    public function testFilter(): void
    {
        $input = $this->createCollection(self::EXAMPLE_DATA);

        self::assertEquals(
            $this->createCollection(['A' => 'foo']),
            $input->filter(static fn ($value, $key): bool => $value === 'foo')
        );
    }

    public function testMap(): void
    {
        $input = $this->createCollection(self::EXAMPLE_DATA);

        self::assertEquals(
            $this->createCollection([
                'A' => 'FOO',
                'B' => 'BAR',
                'C' => 'BAZ',
            ]),
            $input->map(static fn ($value): string => strtoupper($value))
        );
    }

    public function testExists(): void
    {
        $map = $this->createCollection(self::EXAMPLE_DATA);

        self::assertTrue($map->exists(static fn ($value, $key) => $value === 'foo'));
        self::assertFalse($map->exists(static fn ($value, $key) => $value === 'non-existing'));
    }

    public function testForAll(): void
    {
        $map = $this->createCollection(self::EXAMPLE_DATA);

        self::assertTrue($map->forAll(static fn ($value, $key) => strlen($value) > 2));
        self::assertFalse($map->forAll(static fn ($value, $key) => $value === 'foo'));
    }

    protected function getExampleData(): array
    {
        return self::EXAMPLE_DATA;
    }

    /**
     * @return MapInterface|StreamableInterface
     */
    protected function createCollection(array $data): MapInterface
    {
        return new ArrayMap($data);
    }
}
