<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\FieldType\FieldStorage;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\StorageRegistry;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\StorageHandler
 */
class StorageHandlerTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\StorageRegistry&\PHPUnit\Framework\MockObject\MockObject */
    protected StorageRegistry $storageRegistryMock;

    protected StorageHandler $storageHandler;

    /** @var \Ibexa\Contracts\Core\FieldType\FieldStorage&\PHPUnit\Framework\MockObject\MockObject */
    protected FieldStorage $storageMock;

    protected VersionInfo $versionInfoMock;

    public function testStoreFieldData(): void
    {
        $storageMock = $this->getStorageMock();
        $storageRegistryMock = $this->getStorageRegistryMock();

        $storageMock->expects(self::once())
            ->method('storeFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            );

        $storageRegistryMock->expects(self::once())
            ->method('getStorage')
            ->with(self::equalTo('foobar'))
            ->will(self::returnValue($storageMock));

        $field = new Field();
        $field->type = 'foobar';
        $field->value = new FieldValue();

        $handler = $this->getStorageHandler();
        $handler->storeFieldData($this->getVersionInfoMock(), $field);
    }

    public function testGetFieldDataAvailable(): void
    {
        $storageMock = $this->getStorageMock();
        $storageRegistryMock = $this->getStorageRegistryMock();

        $storageMock->expects(self::once())
            ->method('hasFieldData')
            ->will(self::returnValue(true));
        $storageMock->expects(self::once())
            ->method('getFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            );

        $storageRegistryMock->expects(self::once())
            ->method('getStorage')
            ->with(self::equalTo('foobar'))
            ->will(self::returnValue($storageMock));

        $field = new Field();
        $field->id = 123;
        $field->type = 'foobar';
        $field->value = new FieldValue();

        $handler = $this->getStorageHandler();
        $handler->getFieldData($this->getVersionInfoMock(), $field);
    }

    public function testGetFieldDataNotAvailable(): void
    {
        $storageMock = $this->getStorageMock();
        $storageRegistryMock = $this->getStorageRegistryMock();

        $storageMock->expects(self::once())
            ->method('hasFieldData')
            ->will(self::returnValue(false));
        $storageMock->expects(self::never())
            ->method('getFieldData');

        $storageRegistryMock->expects(self::once())
            ->method('getStorage')
            ->with(self::equalTo('foobar'))
            ->will(self::returnValue($storageMock));

        $field = new Field();
        $field->id = 123;
        $field->type = 'foobar';
        $field->value = new FieldValue();

        $handler = $this->getStorageHandler();
        $handler->getFieldData($this->getVersionInfoMock(), $field);
    }

    public function testGetFieldDataNotAvailableForVirtualField(): void
    {
        $storageMock = $this->getStorageMock();
        $storageRegistryMock = $this->getStorageRegistryMock();

        $storageMock->expects(self::never())
            ->method('hasFieldData');

        $storageMock->expects(self::never())
            ->method('getFieldData');

        $storageRegistryMock->expects(self::once())
            ->method('getStorage')
            ->with(self::equalTo('foobar'))
            ->willReturn($storageMock);

        $field = new Field();
        $field->type = 'foobar';
        $field->value = new FieldValue();

        $handler = $this->getStorageHandler();
        $handler->getFieldData($this->getVersionInfoMock(), $field);
    }

    public function testDeleteFieldData(): void
    {
        $storageMock = $this->getStorageMock();
        $storageRegistryMock = $this->getStorageRegistryMock();

        $storageMock->expects(self::once())
            ->method('deleteFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::equalTo([1, 2, 3])
            );

        $storageRegistryMock->expects(self::once())
            ->method('getStorage')
            ->with(self::equalTo('foobar'))
            ->will(self::returnValue($storageMock));

        $handler = $this->getStorageHandler();
        $handler->deleteFieldData('foobar', new VersionInfo(), [1, 2, 3]);
    }

    /**
     * Returns the StorageHandler to test.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\StorageHandler
     */
    protected function getStorageHandler(): StorageHandler
    {
        if (!isset($this->storageHandler)) {
            $this->storageHandler = new StorageHandler(
                $this->getStorageRegistryMock(),
                $this->getContextMock()
            );
        }

        return $this->storageHandler;
    }

    /**
     * Returns a context mock.
     *
     * @return int[]
     */
    protected function getContextMock(): array
    {
        return [23, 42];
    }

    /**
     * Returns a StorageRegistry mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\StorageRegistry&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStorageRegistryMock(): StorageRegistry
    {
        if (!isset($this->storageRegistryMock)) {
            $this->storageRegistryMock = $this->getMockBuilder(StorageRegistry::class)
                ->setConstructorArgs([[]])
                ->setMethods([])
                ->getMock();
        }

        return $this->storageRegistryMock;
    }

    /**
     * Returns a Storage mock.
     *
     * @return \Ibexa\Contracts\Core\FieldType\FieldStorage&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStorageMock(): FieldStorage
    {
        if (!isset($this->storageMock)) {
            $this->storageMock = $this->createMock(FieldStorage::class);
        }

        return $this->storageMock;
    }

    protected function getVersionInfoMock(): VersionInfo
    {
        if (!isset($this->versionInfoMock)) {
            $this->versionInfoMock = $this->createMock(VersionInfo::class);
        }

        return $this->versionInfoMock;
    }
}
