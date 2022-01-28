<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\Type\StorageRegistry;
use PHPUnit\Framework\TestCase;

final class StorageRegistryTest extends TestCase
{
    public function testHasStorage(): void
    {
        $registry = new StorageRegistry([
            'foo' => $this->createMock(FieldConstraintsStorage::class),
            'bar' => $this->createMock(FieldConstraintsStorage::class),
        ]);

        self::assertTrue($registry->hasStorage('foo'));
        self::assertTrue($registry->hasStorage('bar'));
        // baz field type is not supported
        self::assertFalse($registry->hasStorage('baz'));
    }

    public function testGetStorage(): void
    {
        $storages = [
            'foo' => $this->createMock(FieldConstraintsStorage::class),
            'bar' => $this->createMock(FieldConstraintsStorage::class),
        ];

        $registry = new StorageRegistry($storages);

        self::assertSame($storages['foo'], $registry->getStorage('foo'));
        self::assertSame($storages['bar'], $registry->getStorage('bar'));
    }

    public function testGetStorageForNonSupportedFieldType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Argument \'$typeName\' is invalid: Undefined Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage for "baz" field type');

        $registry = new StorageRegistry([
            'foo' => $this->createMock(FieldConstraintsStorage::class),
            'bar' => $this->createMock(FieldConstraintsStorage::class),
        ]);
        $registry->getStorage('baz');
    }
}
