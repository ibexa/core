<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Mapper;

use Ibexa\Contracts\Core\Event\Mapper\ResolveMissingFieldEvent;
use Ibexa\Contracts\Core\FieldType\DefaultDataFieldStorage;
use Ibexa\Contracts\Core\FieldType\FieldStorage;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\NullStorage;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber;
use Ibexa\Core\Persistence\Legacy\Content\StorageRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

final class ResolveVirtualFieldSubscriberTest extends TestCase
{
    public function testResolveVirtualField(): void
    {
        $converterRegistry = $this->getConverterRegistry();

        $contentGateway = $this->createMock(ContentGateway::class);
        $contentGateway->expects(self::never())->method('insertNewField');

        $storageRegistry = $this->createMock(StorageRegistry::class);
        $storageRegistry->method('getStorage')->willReturn(new NullStorage());

        $eventDispatcher = $this->getEventDispatcher(
            $converterRegistry,
            $storageRegistry,
            $contentGateway
        );

        $event = $eventDispatcher->dispatch(
            $this->getEvent([
                'id' => 123,
                'identifier' => 'example_field',
                'fieldType' => 'some_type',
                'defaultValue' => new Content\FieldValue(),
            ])
        );

        $expected = new Field([
            'id' => null,
            'fieldDefinitionId' => 123,
            'type' => 'some_type',
            'value' => new Content\FieldValue(),
            'languageCode' => 'eng-GB',
            'versionNo' => 123,
        ]);

        self::assertEquals(
            $expected,
            $event->getField()
        );

        self::assertCount(3, $eventDispatcher->getCalledListeners());
        self::assertEquals(
            [
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::resolveVirtualField',
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::resolveVirtualExternalStorageField',
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::persistExternalStorageField',
            ],
            array_column($eventDispatcher->getCalledListeners(), 'pretty')
        );
    }

    public function testResolveVirtualExternalStorageField(): void
    {
        $converterRegistry = $this->getConverterRegistry();

        $contentGateway = $this->createMock(ContentGateway::class);
        $contentGateway->expects(self::never())->method('insertNewField');

        $defaultFieldStorageMock = $this->createMock(DefaultDataFieldStorage::class);
        $defaultFieldStorageMock
            ->method('getDefaultFieldData')
            ->willReturnCallback(
                static function (
                    VersionInfo $versionInfo,
                    Field $field
                ): void {
                    $field->value->externalData = [
                        'some_default' => 'external_data',
                    ];
                }
            );
        $storageRegistry = $this->createMock(StorageRegistry::class);
        $storageRegistry->method('getStorage')
            ->willReturn($defaultFieldStorageMock);

        $eventDispatcher = $this->getEventDispatcher(
            $converterRegistry,
            $storageRegistry,
            $contentGateway
        );

        $event = $eventDispatcher->dispatch(
            $this->getEvent([
                'id' => 678,
                'identifier' => 'example_external_field',
                'fieldType' => 'external_type_virtual',
                'defaultValue' => new Content\FieldValue(),
            ])
        );

        $expected = new Field([
            'id' => null,
            'fieldDefinitionId' => 678,
            'type' => 'external_type_virtual',
            'value' => new Content\FieldValue([
                'externalData' => [
                    'some_default' => 'external_data',
                ],
            ]),
            'languageCode' => 'eng-GB',
            'versionNo' => 123,
        ]);

        self::assertEquals(
            $expected,
            $event->getField()
        );

        self::assertCount(1, $eventDispatcher->getNotCalledListeners());
        self::assertEquals(
            'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::persistExternalStorageField',
            $eventDispatcher->getNotCalledListeners()[0]['pretty']
        );
    }

    public function testPersistEmptyExternalStorageField(): void
    {
        $converterRegistry = $this->getConverterRegistry();

        $storage = $this->createMock(FieldStorage::class);
        $storage->expects(self::never())->method('storeFieldData');

        $storage->expects(self::once())
            ->method('getFieldData')
            ->willReturnCallback(static function (
                VersionInfo $versionInfo,
                Field $field
            ) {
                $field->value->externalData = [
                    'some_default' => 'external_data',
                ];
            });

        $storageRegistry = $this->createMock(StorageRegistry::class);
        $storageRegistry->method('getStorage')->willReturn($storage);

        $contentGateway = $this->createMock(ContentGateway::class);
        $contentGateway->expects(self::once())->method('insertNewField')
            ->willReturn(567);

        $eventDispatcher = $this->getEventDispatcher(
            $converterRegistry,
            $storageRegistry,
            $contentGateway
        );

        $event = $eventDispatcher->dispatch(
            $this->getEvent([
                'id' => 123,
                'identifier' => 'example_field',
                'fieldType' => 'external_type',
                'defaultValue' => new Content\FieldValue(),
            ])
        );

        $expected = new Field([
            'id' => 567,
            'fieldDefinitionId' => 123,
            'type' => 'external_type',
            'value' => new Content\FieldValue([
                'externalData' => [
                    'some_default' => 'external_data',
                ],
            ]),
            'languageCode' => 'eng-GB',
            'versionNo' => 123,
        ]);

        self::assertEquals(
            $expected,
            $event->getField()
        );

        self::assertCount(3, $eventDispatcher->getCalledListeners());
        self::assertEquals(
            [
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::resolveVirtualField',
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::resolveVirtualExternalStorageField',
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::persistExternalStorageField',
            ],
            array_column($eventDispatcher->getCalledListeners(), 'pretty')
        );
    }

    public function testPersistExternalStorageField(): void
    {
        $converterRegistry = $this->getConverterRegistry();

        $storage = $this->createMock(FieldStorage::class);
        $storage->expects(self::once())
            ->method('storeFieldData')
            ->willReturnCallback(static function (
                VersionInfo $versionInfo,
                Field $field
            ) {
                $field->value->externalData = $field->value->data;
            });

        $storage->expects(self::once())->method('getFieldData');

        $storageRegistry = $this->createMock(StorageRegistry::class);
        $storageRegistry->method('getStorage')->willReturn($storage);

        $contentGateway = $this->createMock(ContentGateway::class);
        $contentGateway->expects(self::once())->method('insertNewField')
            ->willReturn(456);

        $eventDispatcher = $this->getEventDispatcher(
            $converterRegistry,
            $storageRegistry,
            $contentGateway
        );

        $event = $eventDispatcher->dispatch(
            $this->getEvent([
                'id' => 123,
                'identifier' => 'example_field',
                'fieldType' => 'external_type',
                'defaultValue' => new Content\FieldValue([
                    'data' => ['some_data' => 'to_be_stored'],
                ]),
            ])
        );

        $expected = new Field([
            'id' => 456,
            'fieldDefinitionId' => 123,
            'type' => 'external_type',
            'value' => new Content\FieldValue([
                'data' => [
                    'some_data' => 'to_be_stored',
                ],
                'externalData' => [
                    'some_data' => 'to_be_stored',
                ],
            ]),
            'languageCode' => 'eng-GB',
            'versionNo' => 123,
        ]);

        self::assertEquals(
            $expected,
            $event->getField()
        );

        self::assertCount(3, $eventDispatcher->getCalledListeners());
        self::assertEquals(
            [
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::resolveVirtualField',
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::resolveVirtualExternalStorageField',
                'Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber::persistExternalStorageField',
            ],
            array_column($eventDispatcher->getCalledListeners(), 'pretty')
        );
    }

    private function getContent(): Content
    {
        $versionInfo = $this->getVersionInfo();

        $content = new Content();
        $content->versionInfo = $versionInfo;
        $content->fields = [];

        return $content;
    }

    private function getVersionInfo(): VersionInfo
    {
        $versionInfo = new VersionInfo();
        $versionInfo->versionNo = 123;

        return $versionInfo;
    }

    private function getEventDispatcher(
        ConverterRegistry $converterRegistry,
        StorageRegistry $storageRegistry,
        ContentGateway $contentGateway
    ): TraceableEventDispatcher {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(
            new ResolveVirtualFieldSubscriber(
                $converterRegistry,
                $storageRegistry,
                $contentGateway,
            )
        );

        return new TraceableEventDispatcher(
            $eventDispatcher,
            new Stopwatch()
        );
    }

    private function getConverterRegistry(): ConverterRegistry
    {
        $converterRegistry = $this->createMock(ConverterRegistry::class);
        $converterRegistry->method('getConverter')
            ->willReturn($this->createMock(Converter::class));

        return $converterRegistry;
    }

    /**
     * @param array<string, mixed> $fieldDefinition
     */
    private function getEvent(array $fieldDefinition): ResolveMissingFieldEvent
    {
        return new ResolveMissingFieldEvent(
            $this->getContent(),
            new FieldDefinition($fieldDefinition),
            'eng-GB'
        );
    }
}
