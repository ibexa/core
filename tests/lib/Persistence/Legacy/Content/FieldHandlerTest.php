<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Persistence\FieldType as SPIFieldType;
use Ibexa\Core\Persistence\FieldTypeRegistry;
use Ibexa\Core\Persistence\Legacy\Content\FieldHandler;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FieldHandler::class)]
class FieldHandlerTest extends LanguageAwareTestCase
{
    /**
     * Gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Gateway
     */
    protected $contentGatewayMock;

    /**
     * Mapper mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Mapper
     */
    protected $mapperMock;

    /**
     * Storage handler mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\StorageHandler
     */
    protected $storageHandlerMock;

    /**
     * Field type registry mock.
     *
     * @var \Ibexa\Core\Persistence\FieldTypeRegistry
     */
    protected $fieldTypeRegistryMock;

    /**
     * Field type mock.
     *
     * @var \Ibexa\Contracts\Core\FieldType\FieldType
     */
    protected $fieldTypeMock;

    /**
     * @param bool $storageHandlerUpdatesFields
     */
    protected function assertCreateNewFields($storageHandlerUpdatesFields = false)
    {
        $contentGatewayMock = $this->getContentGatewayMock();
        $fieldTypeMock = $this->getFieldTypeMock();
        $storageHandlerMock = $this->getStorageHandlerMock();

        $fieldTypeMock->expects(self::exactly(3))
            ->method('getEmptyValue')
            ->will(self::returnValue(new FieldValue()));

        $contentGatewayMock->expects(self::exactly(6))
            ->method('insertNewField')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            )->will(self::returnValue(42));

        $expectedStoreFields = [];
        $fieldValue = new FieldValue();
        foreach ([1, 2, 3] as $fieldDefinitionId) {
            foreach (['eng-US', 'eng-GB'] as $languageCode) {
                $field = new Field(
                    [
                        'id' => 42,
                        'fieldDefinitionId' => $fieldDefinitionId,
                        'type' => 'some-type',
                        'versionNo' => 1,
                        'value' => $fieldValue,
                        'languageCode' => $languageCode,
                    ]
                );
                // This field is copied from main language
                if ($fieldDefinitionId == 2 && $languageCode == 'eng-US') {
                    $copyField = clone $field;
                    $originalField = clone $field;
                    $originalField->languageCode = 'eng-GB';
                    continue;
                }
                $expectedStoreFields[] = $field;
            }
        }

        $invocationOrder = 0;
        $storageHandlerMock->expects(self::exactly(5))
            ->method('storeFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $expectedStoreFields, $storageHandlerUpdatesFields) {
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($expectedStoreFields[$invocationOrder], $parameters[1]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });

        /* @var $copyField */
        /* @var $originalField */
        $storageHandlerMock->expects(self::once())
            ->method('copyFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $copyField, $originalField, $storageHandlerUpdatesFields) {
                $this->assertSame(5, $invocationOrder);
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($copyField, $parameters[1]);
                $this->assertEquals($originalField, $parameters[2]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });
    }

    public function testCreateNewFields(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateNewFields(false);

        $mapperMock->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $fieldHandler->createNewFields(
            $this->getContentPartialFieldsFixture(),
            $this->getContentTypeFixture()
        );
    }

    public function testCreateNewFieldsUpdatingStorageHandler(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $contentGatewayMock = $this->getContentGatewayMock();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateNewFields(true);

        $mapperMock->expects(self::exactly(12))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(6))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $fieldHandler->createNewFields(
            $this->getContentPartialFieldsFixture(),
            $this->getContentTypeFixture()
        );
    }

    /**
     * @param bool $storageHandlerUpdatesFields
     */
    protected function assertCreateNewFieldsForMainLanguage($storageHandlerUpdatesFields = false)
    {
        $contentGatewayMock = $this->getContentGatewayMock();
        $fieldTypeMock = $this->getFieldTypeMock();
        $storageHandlerMock = $this->getStorageHandlerMock();

        $fieldTypeMock->expects(self::exactly(3))
            ->method('getEmptyValue')
            ->will(self::returnValue(new FieldValue()));

        $contentGatewayMock->expects(self::exactly(3))
            ->method('insertNewField')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            )->will(self::returnValue(42));

        $expectedFields = [];
        $fieldValue = new FieldValue();
        foreach ([1, 2, 3] as $fieldDefinitionId) {
            $expectedFields[] = new Field(
                [
                    'id' => 42,
                    'fieldDefinitionId' => $fieldDefinitionId,
                    'type' => 'some-type',
                    'versionNo' => 1,
                    'value' => $fieldValue,
                    'languageCode' => 'eng-GB',
                ]
            );
        }

        $matcher = self::exactly(3);
        $storageHandlerMock->expects($matcher)
            ->method('storeFieldData')
            ->willReturnCallback(function (...$parameters) use ($matcher, $expectedFields, $storageHandlerUpdatesFields) {
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($expectedFields[$matcher->numberOfInvocations() - 1], $parameters[1]);

                return $storageHandlerUpdatesFields;
            });
    }

    public function testCreateNewFieldsForMainLanguage(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateNewFieldsForMainLanguage(false);

        $mapperMock->expects(self::exactly(3))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $fieldHandler->createNewFields(
            $this->getContentNoFieldsFixture(),
            $this->getContentTypeFixture()
        );
    }

    public function testCreateNewFieldsForMainLanguageUpdatingStorageHandler(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $contentGatewayMock = $this->getContentGatewayMock();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateNewFieldsForMainLanguage(true);

        $mapperMock->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(3))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $fieldHandler->createNewFields(
            $this->getContentNoFieldsFixture(),
            $this->getContentTypeFixture()
        );
    }

    /**
     * @param bool $storageHandlerUpdatesFields
     */
    protected function assertCreateExistingFieldsInNewVersion($storageHandlerUpdatesFields = false)
    {
        $contentGatewayMock = $this->getContentGatewayMock();
        $storageHandlerMock = $this->getStorageHandlerMock();

        $contentGatewayMock->expects(self::exactly(6))
            ->method('insertExistingField')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            )->will(self::returnValue(42));

        $expectedCopyCalls = [];
        $fieldValue = new FieldValue();
        foreach ([1, 2, 3] as $fieldDefinitionId) {
            foreach (['eng-US', 'eng-GB'] as $languageIndex => $languageCode) {
                $field = new Field(
                    [
                        'id' => $fieldDefinitionId * 10 + $languageIndex + 1,
                        'fieldDefinitionId' => $fieldDefinitionId,
                        'type' => 'some-type',
                        'value' => $fieldValue,
                        'languageCode' => $languageCode,
                    ]
                );
                $originalField = clone $field;
                $field->versionNo = 1;
                $expectedCopyCalls[] = ['field' => $field, 'original' => $originalField];
            }
        }

        $matcher = self::exactly(6);
        $storageHandlerMock->expects($matcher)
            ->method('copyFieldData')
            ->willReturnCallback(function (...$parameters) use ($matcher, $expectedCopyCalls, $storageHandlerUpdatesFields) {
                $index = $matcher->numberOfInvocations() - 1;
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($expectedCopyCalls[$index]['field'], $parameters[1]);
                $this->assertEquals($expectedCopyCalls[$index]['original'], $parameters[2]);

                return $storageHandlerUpdatesFields;
            });
    }

    public function testCreateExistingFieldsInNewVersion(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateExistingFieldsInNewVersion(false);

        $mapperMock->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $fieldHandler->createExistingFieldsInNewVersion($this->getContentFixture());
    }

    public function testCreateExistingFieldsInNewVersionUpdatingStorageHandler(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $contentGatewayMock = $this->getContentGatewayMock();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateExistingFieldsInNewVersion(true);

        $mapperMock->expects(self::exactly(12))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(6))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $fieldHandler->createExistingFieldsInNewVersion($this->getContentFixture());
    }

    public function testCreateExistingFieldsInNewVersionWithEditedLanguageCode(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $contentGatewayMock = $this->getContentGatewayMock();
        $storageHandlerMock = $this->getStorageHandlerMock();
        $mapperMock = $this->getMapperMock();

        $content = $this->getContentFixture();
        $editedLanguageCode = 'eng-US';

        $editedFieldCount = 0;
        $untouchedFieldCount = 0;
        foreach ($content->fields as $field) {
            if ($field->id === null) {
                continue;
            }
            if ($field->languageCode === $editedLanguageCode) {
                ++$editedFieldCount;
            } else {
                ++$untouchedFieldCount;
            }
        }

        $totalFieldCount = $editedFieldCount + $untouchedFieldCount;

        $contentGatewayMock
            ->expects(self::exactly($totalFieldCount))
            ->method('insertExistingField');

        $storageHandlerMock
            ->expects(self::exactly($editedFieldCount))
            ->method('copyFieldData')
            ->willReturn(false);

        $storageHandlerMock
            ->expects(self::exactly($untouchedFieldCount))
            ->method('referenceFieldData')
            ->willReturn(false);

        $mapperMock
            ->expects(self::exactly($totalFieldCount))
            ->method('convertToStorageValue')
            ->willReturn(new StorageFieldValue());

        $fieldHandler->createExistingFieldsInNewVersion($content, $editedLanguageCode);
    }

    public function testCreateExistingFieldsInNewVersionWithNullLanguageCodeCopiesAll(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $storageHandlerMock = $this->getStorageHandlerMock();
        $mapperMock = $this->getMapperMock();

        $this->assertCreateExistingFieldsInNewVersion();

        $storageHandlerMock
            ->expects(self::never())
            ->method('referenceFieldData');

        $mapperMock
            ->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->willReturn(new StorageFieldValue());

        $fieldHandler->createExistingFieldsInNewVersion($this->getContentFixture(), null);
    }

    public function testCreateExistingFieldsInNewVersionAllFieldsReferencedWhenEditedLanguageNotInContent(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $contentGatewayMock = $this->getContentGatewayMock();
        $storageHandlerMock = $this->getStorageHandlerMock();
        $mapperMock = $this->getMapperMock();

        $content = $this->getContentSingleLanguageFixture();

        // 3 real eng-GB fields, editing ger-DE which doesn't exist — all go through reference path
        $contentGatewayMock
            ->expects(self::exactly(3))
            ->method('insertExistingField');

        $storageHandlerMock
            ->expects(self::exactly(3))
            ->method('referenceFieldData')
            ->willReturn(false);

        $storageHandlerMock
            ->expects(self::never())
            ->method('copyFieldData');

        $mapperMock
            ->expects(self::exactly(3))
            ->method('convertToStorageValue')
            ->willReturn(new StorageFieldValue());

        $fieldHandler->createExistingFieldsInNewVersion($content, 'ger-DE');
    }

    public function testLoadExternalFieldData(): void
    {
        $fieldHandler = $this->getFieldHandler();

        $storageHandlerMock = $this->getStorageHandlerMock();

        $storageHandlerMock->expects(self::exactly(6))
            ->method('getFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            );

        $fieldHandler->loadExternalFieldData($this->getContentFixture());
    }

    /**
     * @param bool $storageHandlerUpdatesFields
     */
    public function assertUpdateFieldsWithNewLanguage($storageHandlerUpdatesFields = false)
    {
        $contentGatewayMock = $this->getContentGatewayMock();
        $fieldTypeMock = $this->getFieldTypeMock();
        $storageHandlerMock = $this->getStorageHandlerMock();

        $fieldTypeMock->expects(self::exactly(1))
            ->method('getEmptyValue')
            ->will(self::returnValue(new FieldValue()));

        $contentGatewayMock->expects(self::exactly(3))
            ->method('insertNewField')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $expectedStoreFields = [];
        $fieldValue = new FieldValue();
        foreach ([1, 2, 3] as $fieldDefinitionId) {
            $field = new Field(
                [
                    'fieldDefinitionId' => $fieldDefinitionId,
                    'type' => 'some-type',
                    'versionNo' => 1,
                    'value' => $fieldValue,
                    'languageCode' => 'ger-DE',
                ]
            );
            // This field is copied from main language
            if ($fieldDefinitionId == 3) {
                $copyField = clone $field;
                $originalField = clone $field;
                $originalField->id = $fieldDefinitionId * 10 + 2;
                $originalField->languageCode = 'eng-GB';
                continue;
            }
            $expectedStoreFields[] = $field;
        }

        $invocationOrder = 0;
        $storageHandlerMock->expects(self::exactly(2))
            ->method('storeFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $expectedStoreFields, $storageHandlerUpdatesFields) {
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($expectedStoreFields[$invocationOrder], $parameters[1]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });

        /* @var $copyField */
        /* @var $originalField */
        $storageHandlerMock->expects(self::once())
            ->method('copyFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $copyField, $originalField, $storageHandlerUpdatesFields) {
                $this->assertSame(2, $invocationOrder);
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($copyField, $parameters[1]);
                $this->assertEquals($originalField, $parameters[2]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });
    }

    public function testUpdateFieldsWithNewLanguage(): void
    {
        $mapperMock = $this->getMapperMock();
        $fieldHandler = $this->getFieldHandler();

        $this->assertUpdateFieldsWithNewLanguage(false);

        $mapperMock->expects(self::exactly(3))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $field = new Field(
            [
                'type' => 'some-type',
                'value' => new FieldValue(),
                'fieldDefinitionId' => 2,
                'languageCode' => 'ger-DE',
            ]
        );
        $fieldHandler->updateFields(
            $this->getContentFixture(),
            new UpdateStruct(
                [
                    'initialLanguageId' => 8,
                    'fields' => [$field],
                ]
            ),
            $this->getContentTypeFixture()
        );
    }

    public function testUpdateFieldsWithNewLanguageUpdatingStorageHandler(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();
        $contentGatewayMock = $this->getContentGatewayMock();

        $this->assertUpdateFieldsWithNewLanguage(true);

        $mapperMock->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(3))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $field = new Field(
            [
                'type' => 'some-type',
                'value' => new FieldValue(),
                'fieldDefinitionId' => 2,
                'languageCode' => 'ger-DE',
            ]
        );
        $fieldHandler->updateFields(
            $this->getContentFixture(),
            new UpdateStruct(
                [
                    'initialLanguageId' => 8,
                    'fields' => [$field],
                ]
            ),
            $this->getContentTypeFixture()
        );
    }

    /**
     * @param bool $storageHandlerUpdatesFields
     */
    public function assertUpdateFieldsExistingLanguages($storageHandlerUpdatesFields = false)
    {
        $storageHandlerMock = $this->getStorageHandlerMock();

        $expectedStoreFields = [];
        $fieldValue = new FieldValue();
        $fieldsToCopy = [];
        foreach ([1, 2, 3] as $fieldDefinitionId) {
            foreach (['eng-US', 'eng-GB'] as $languageIndex => $languageCode) {
                $field = new Field(
                    [
                        'id' => $fieldDefinitionId * 10 + $languageIndex + 1,
                        'fieldDefinitionId' => $fieldDefinitionId,
                        'type' => 'some-type',
                        'versionNo' => 1,
                        'value' => $fieldValue,
                        'languageCode' => $languageCode,
                    ]
                );
                // These fields are copied from main language
                if (($fieldDefinitionId == 2 || $fieldDefinitionId == 3) && $languageCode != 'eng-GB') {
                    $originalField = clone $field;
                    $originalField->id = $fieldDefinitionId * 10 + $languageIndex + 2;
                    $originalField->languageCode = 'eng-GB';
                    $fieldsToCopy[] = [
                        'copy' => clone $field,
                        'original' => $originalField,
                    ];
                } else {
                    $expectedStoreFields[] = $field;
                }
            }
        }

        $invocationOrder = 0;
        $storeCount = count($expectedStoreFields);
        $storageHandlerMock->expects(self::exactly($storeCount))
            ->method('storeFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $expectedStoreFields, $storageHandlerUpdatesFields) {
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($expectedStoreFields[$invocationOrder], $parameters[1]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });

        $storageHandlerMock->expects(self::exactly(count($fieldsToCopy)))
            ->method('copyFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $fieldsToCopy, $storeCount, $storageHandlerUpdatesFields) {
                $index = $invocationOrder - $storeCount;
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($fieldsToCopy[$index]['copy'], $parameters[1]);
                $this->assertEquals($fieldsToCopy[$index]['original'], $parameters[2]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });
    }

    public function testUpdateFieldsExistingLanguages(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();
        $contentGatewayMock = $this->getContentGatewayMock();

        $this->assertUpdateFieldsExistingLanguages(false);

        $mapperMock->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(6))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $fieldHandler->updateFields(
            $this->getContentFixture(),
            $this->getUpdateStructFixture(),
            $this->getContentTypeFixture()
        );
    }

    public function testUpdateFieldsExistingLanguagesUpdatingStorageHandler(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();
        $contentGatewayMock = $this->getContentGatewayMock();

        $this->assertUpdateFieldsExistingLanguages(true);

        $mapperMock->expects(self::exactly(12))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(12))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $fieldHandler->updateFields(
            $this->getContentFixture(),
            $this->getUpdateStructFixture(),
            $this->getContentTypeFixture()
        );
    }

    /**
     * @param bool $storageHandlerUpdatesFields
     */
    public function assertUpdateFieldsForInitialLanguage($storageHandlerUpdatesFields = false)
    {
        $storageHandlerMock = $this->getStorageHandlerMock();

        $expectedStoreFields = [];
        $fieldValue = new FieldValue();
        $fieldsToCopy = [];
        foreach ([1, 2, 3] as $id => $fieldDefinitionId) {
            $field = new Field(
                [
                    'fieldDefinitionId' => $fieldDefinitionId,
                    'type' => 'some-type',
                    'versionNo' => 1,
                    'value' => $fieldValue,
                    'languageCode' => 'eng-US',
                ]
            );
            // These fields are copied from main language
            if ($fieldDefinitionId == 2 || $fieldDefinitionId == 3) {
                $originalField = clone $field;
                $originalField->id = $fieldDefinitionId;
                $originalField->languageCode = 'eng-GB';
                $fieldsToCopy[] = [
                    'copy' => clone $field,
                    'original' => $originalField,
                ];
                continue;
            }
            // This field is inserted as empty
            $field->value = null;
            $expectedStoreFields[] = $field;
        }

        $invocationOrder = 0;
        $storeCount = count($expectedStoreFields);
        $storageHandlerMock->expects(self::exactly($storeCount))
            ->method('storeFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $expectedStoreFields, $storageHandlerUpdatesFields) {
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($expectedStoreFields[$invocationOrder], $parameters[1]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });

        $storageHandlerMock->expects(self::exactly(count($fieldsToCopy)))
            ->method('copyFieldData')
            ->willReturnCallback(function (...$parameters) use (&$invocationOrder, $fieldsToCopy, $storeCount, $storageHandlerUpdatesFields) {
                $index = $invocationOrder - $storeCount;
                $this->assertInstanceOf(VersionInfo::class, $parameters[0]);
                $this->assertEquals($fieldsToCopy[$index]['copy'], $parameters[1]);
                $this->assertEquals($fieldsToCopy[$index]['original'], $parameters[2]);
                ++$invocationOrder;

                return $storageHandlerUpdatesFields;
            });
    }

    public function testUpdateFieldsForInitialLanguage(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();

        $this->assertUpdateFieldsForInitialLanguage(false);

        $mapperMock->expects(self::exactly(3))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $struct = new UpdateStruct();
        // Language with id=2 is eng-US
        $struct->initialLanguageId = 2;
        $fieldHandler->updateFields(
            $this->getContentSingleLanguageFixture(),
            $struct,
            $this->getContentTypeFixture()
        );
    }

    public function testUpdateFieldsForInitialLanguageUpdatingStorageHandler(): void
    {
        $fieldHandler = $this->getFieldHandler();
        $mapperMock = $this->getMapperMock();
        $contentGatewayMock = $this->getContentGatewayMock();

        $this->assertUpdateFieldsForInitialLanguage(true);

        $mapperMock->expects(self::exactly(6))
            ->method('convertToStorageValue')
            ->with(self::isInstanceOf(Field::class))
            ->will(self::returnValue(new StorageFieldValue()));

        $contentGatewayMock->expects(self::exactly(3))
            ->method('updateField')
            ->with(
                self::isInstanceOf(Field::class),
                self::isInstanceOf(StorageFieldValue::class)
            );

        $struct = new UpdateStruct();
        // Language with id=2 is eng-US
        $struct->initialLanguageId = 2;
        $fieldHandler->updateFields(
            $this->getContentSingleLanguageFixture(),
            $struct,
            $this->getContentTypeFixture()
        );
    }

    public function testDeleteFields(): void
    {
        $fieldHandler = $this->getFieldHandler();

        $contentGatewayMock = $this->getContentGatewayMock();
        $contentGatewayMock->expects(self::once())
            ->method('getFieldIdsByType')
            ->with(
                self::equalTo(42),
                self::equalTo(2)
            )->will(self::returnValue(['some-type' => [2, 3]]));

        $storageHandlerMock = $this->getStorageHandlerMock();
        $storageHandlerMock->expects(self::once())
            ->method('deleteFieldData')
            ->with(
                self::equalTo('some-type'),
                self::isInstanceOf(VersionInfo::class),
                self::equalTo([2, 3])
            );

        $contentGatewayMock->expects(self::once())
            ->method('deleteFields')
            ->with(
                self::equalTo(42),
                self::equalTo(2)
            );

        $fieldHandler->deleteFields(42, new VersionInfo(['versionNo' => 2]));
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentPartialFieldsFixture()
    {
        $content = new Content();
        $content->versionInfo = new VersionInfo();
        $content->versionInfo->versionNo = 1;
        $content->versionInfo->languageCodes = ['eng-US', 'eng-GB'];
        $content->versionInfo->contentInfo = new ContentInfo();
        $content->versionInfo->contentInfo->id = 42;
        $content->versionInfo->contentInfo->contentTypeId = 1;
        $content->versionInfo->contentInfo->mainLanguageCode = 'eng-GB';

        $field = new Field();
        $field->type = 'some-type';
        $field->value = new FieldValue();

        $firstFieldUs = clone $field;
        $firstFieldUs->id = 11;
        $firstFieldUs->fieldDefinitionId = 1;
        $firstFieldUs->languageCode = 'eng-US';

        $secondFieldGb = clone $field;
        $secondFieldGb->id = 22;
        $secondFieldGb->fieldDefinitionId = 2;
        $secondFieldGb->languageCode = 'eng-GB';

        $content->fields = [
            $firstFieldUs,
            $secondFieldGb,
        ];

        return $content;
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentNoFieldsFixture()
    {
        $content = new Content();
        $content->versionInfo = new VersionInfo();
        $content->versionInfo->versionNo = 1;
        $content->versionInfo->languageCodes = ['eng-US', 'eng-GB'];
        $content->versionInfo->contentInfo = new ContentInfo();
        $content->versionInfo->contentInfo->id = 42;
        $content->versionInfo->contentInfo->contentTypeId = 1;
        $content->versionInfo->contentInfo->mainLanguageCode = 'eng-GB';
        $content->fields = [];

        return $content;
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentSingleLanguageFixture()
    {
        $content = new Content();
        $content->versionInfo = new VersionInfo();
        $content->versionInfo->versionNo = 1;
        $content->versionInfo->languageCodes = ['eng-GB'];
        $content->versionInfo->contentInfo = new ContentInfo();
        $content->versionInfo->contentInfo->id = 42;
        $content->versionInfo->contentInfo->contentTypeId = 1;
        $content->versionInfo->contentInfo->mainLanguageCode = 'eng-GB';

        $field = new Field();
        $field->type = 'some-type';
        $field->value = new FieldValue();
        $field->languageCode = 'eng-GB';

        foreach ([1, 2, 3] as $id) {
            $contentField = clone $field;
            $contentField->id = $id;
            $contentField->fieldDefinitionId = $id;

            $content->fields[] = $contentField;
        }

        return $content;
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentFixture()
    {
        $content = $this->getContentPartialFieldsFixture();

        $field = new Field();
        $field->type = 'some-type';
        $field->value = new FieldValue();

        $firstFieldGb = clone $field;
        $firstFieldGb->id = 12;
        $firstFieldGb->fieldDefinitionId = 1;
        $firstFieldGb->languageCode = 'eng-GB';

        $secondFieldUs = clone $field;
        $secondFieldUs->id = 21;
        $secondFieldUs->fieldDefinitionId = 2;
        $secondFieldUs->languageCode = 'eng-US';

        $thirdFieldGb = clone $field;
        $thirdFieldGb->id = 32;
        $thirdFieldGb->fieldDefinitionId = 3;
        $thirdFieldGb->languageCode = 'eng-GB';

        $thirdFieldUs = clone $field;
        $thirdFieldUs->id = 31;
        $thirdFieldUs->fieldDefinitionId = 3;
        $thirdFieldUs->languageCode = 'eng-US';

        $content->fields = [
            $content->fields[0],
            $firstFieldGb,
            $secondFieldUs,
            $content->fields[1],
            $thirdFieldUs,
            $thirdFieldGb,
        ];

        return $content;
    }

    /**
     * Returns a ContentType fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    protected function getContentTypeFixture()
    {
        $contentType = new Type();
        $firstFieldDefinition = new FieldDefinition(
            [
                'id' => 1,
                'fieldType' => 'some-type',
                'isTranslatable' => true,
            ]
        );
        $secondFieldDefinition = new FieldDefinition(
            [
                'id' => 2,
                'fieldType' => 'some-type',
                'isTranslatable' => false,
            ]
        );
        $thirdFieldDefinition = new FieldDefinition(
            [
                'id' => 3,
                'fieldType' => 'some-type',
                'isTranslatable' => false,
            ]
        );
        $contentType->fieldDefinitions = [
            $firstFieldDefinition,
            $secondFieldDefinition,
            $thirdFieldDefinition,
        ];

        return $contentType;
    }

    /**
     * Returns an UpdateStruct fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\UpdateStruct
     */
    protected function getUpdateStructFixture()
    {
        $struct = new UpdateStruct();

        // Language with id=2 is eng-US
        $struct->initialLanguageId = 2;

        $content = $this->getContentFixture();

        foreach ($content->fields as $field) {
            // Skip untranslatable fields not in main language
            if (($field->fieldDefinitionId == 2 || $field->fieldDefinitionId == 3) && $field->languageCode != 'eng-GB') {
                continue;
            }
            $struct->fields[] = $field;
        }

        return $struct;
    }

    /**
     * Returns a FieldHandler to test.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\FieldHandler
     */
    protected function getFieldHandler()
    {
        $mock = new FieldHandler(
            $this->getContentGatewayMock(),
            $this->getMapperMock(),
            $this->getStorageHandlerMock(),
            $this->getLanguageHandler(),
            $this->getFieldTypeRegistryMock()
        );

        return $mock;
    }

    /**
     * Returns a StorageHandler mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\StorageHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStorageHandlerMock()
    {
        if (!isset($this->storageHandlerMock)) {
            $this->storageHandlerMock = $this->createMock(StorageHandler::class);
        }

        return $this->storageHandlerMock;
    }

    /**
     * Returns a Mapper mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Mapper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMapperMock()
    {
        if (!isset($this->mapperMock)) {
            $this->mapperMock = $this->createMock(Mapper::class);
        }

        return $this->mapperMock;
    }

    /**
     * Returns a mock object for the Content Gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Gateway|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /**
     * @return \Ibexa\Core\Persistence\FieldTypeRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFieldTypeRegistryMock()
    {
        if (!isset($this->fieldTypeRegistryMock)) {
            $this->fieldTypeRegistryMock = $this->createMock(FieldTypeRegistry::class);

            $this->fieldTypeRegistryMock->expects(
                self::any()
            )->method(
                'getFieldType'
            )->with(
                self::isString()
            )->will(
                self::returnValue($this->getFieldTypeMock())
            );
        }

        return $this->fieldTypeRegistryMock;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\FieldType|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFieldTypeMock()
    {
        if (!isset($this->fieldTypeMock)) {
            $this->fieldTypeMock = $this->createMock(SPIFieldType::class);
        }

        return $this->fieldTypeMock;
    }
}
