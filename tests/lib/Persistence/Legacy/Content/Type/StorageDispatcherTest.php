<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage;
use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Type\StorageDispatcher;
use Ibexa\Core\Persistence\Legacy\Content\Type\StorageRegistryInterface;
use PHPUnit\Framework\TestCase;

final class StorageDispatcherTest extends TestCase
{
    private const EXAMPLE_FIELD_DEFINITION_ID = 1;
    private const EXAMPLE_FIELD_TYPE_IDENTIFIER = 'example_ft';

    public function testStoreFieldConstraintsData(): void
    {
        $constraints = $this->createMock(FieldTypeConstraints::class);

        $storage = $this->createMock(FieldConstraintsStorage::class);
        $storage
            ->expects($this->once())
            ->method('storeFieldConstraintsData')
            ->with(self::EXAMPLE_FIELD_DEFINITION_ID, $constraints);

        $fieldDefinition = new FieldDefinition();
        $fieldDefinition->fieldTypeConstraints = $constraints;
        $fieldDefinition->id = self::EXAMPLE_FIELD_DEFINITION_ID;
        $fieldDefinition->fieldType = self::EXAMPLE_FIELD_TYPE_IDENTIFIER;

        $registry = $this->createStorageRegistryMockWithExternalStorage($storage);

        $dispatcher = new StorageDispatcher($registry);
        $dispatcher->storeFieldConstraintsData($fieldDefinition);
    }

    public function testStoreFieldConstraintsDataForNonSupportedFieldType(): void
    {
        $fieldDefinition = new FieldDefinition();
        $fieldDefinition->id = self::EXAMPLE_FIELD_DEFINITION_ID;
        $fieldDefinition->fieldType = self::EXAMPLE_FIELD_TYPE_IDENTIFIER;

        $registry = $this->createStorageRegistryMockWithoutExternalStorage();

        $dispatcher = new StorageDispatcher($registry);
        $dispatcher->storeFieldConstraintsData($fieldDefinition);
    }

    public function testLoadFieldConstraintsData(): void
    {
        $constraints = $this->createMock(FieldTypeConstraints::class);

        $storage = $this->createMock(FieldConstraintsStorage::class);
        $storage
            ->expects($this->once())
            ->method('getFieldConstraintsData')
            ->with(self::EXAMPLE_FIELD_DEFINITION_ID)
            ->willReturn($constraints);

        $fieldDefinition = new FieldDefinition();
        $fieldDefinition->id = self::EXAMPLE_FIELD_DEFINITION_ID;
        $fieldDefinition->fieldType = self::EXAMPLE_FIELD_TYPE_IDENTIFIER;

        $registry = $this->createStorageRegistryMockWithExternalStorage($storage);

        $dispatcher = new StorageDispatcher($registry);
        $dispatcher->loadFieldConstraintsData($fieldDefinition);

        self::assertSame(
            $constraints,
            $fieldDefinition->fieldTypeConstraints
        );
    }

    public function testLoadFieldConstraintsDataForNonSupportedFieldType(): void
    {
        $constraints = $this->createMock(FieldTypeConstraints::class);

        $fieldDefinition = new FieldDefinition();
        $fieldDefinition->id = self::EXAMPLE_FIELD_DEFINITION_ID;
        $fieldDefinition->fieldType = self::EXAMPLE_FIELD_TYPE_IDENTIFIER;
        $fieldDefinition->fieldTypeConstraints = $constraints;

        $registry = $this->createStorageRegistryMockWithoutExternalStorage();

        $dispatcher = new StorageDispatcher($registry);
        $dispatcher->loadFieldConstraintsData($fieldDefinition);

        self::assertSame(
            $constraints,
            $fieldDefinition->fieldTypeConstraints
        );
    }

    public function testDeleteFieldConstraintsData(): void
    {
        $storage = $this->createMock(FieldConstraintsStorage::class);
        $storage
            ->expects($this->once())
            ->method('deleteFieldConstraintsData')
            ->with(self::EXAMPLE_FIELD_DEFINITION_ID);

        $registry = $this->createStorageRegistryMockWithExternalStorage($storage);

        $dispatcher = new StorageDispatcher($registry);
        $dispatcher->deleteFieldConstraintsData(
            self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
            self::EXAMPLE_FIELD_DEFINITION_ID
        );
    }

    public function testDeleteFieldConstraintsDataForNonSupportedFieldType(): void
    {
        $registry = $this->createStorageRegistryMockWithoutExternalStorage();

        $dispatcher = new StorageDispatcher($registry);
        $dispatcher->deleteFieldConstraintsData(
            self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
            self::EXAMPLE_FIELD_DEFINITION_ID
        );
    }

    private function createStorageRegistryMockWithoutExternalStorage(): StorageRegistryInterface
    {
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('hasStorage')->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)->willReturn(false);
        $registry
            ->expects($this->never())
            ->method('getStorage')
            ->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER);

        return $registry;
    }

    private function createStorageRegistryMockWithExternalStorage(
        FieldConstraintsStorage $storage
    ): StorageRegistryInterface {
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('hasStorage')->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)->willReturn(true);
        $registry->method('getStorage')->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)->willReturn($storage);

        return $registry;
    }
}
