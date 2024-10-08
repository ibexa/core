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

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\FieldHandler
 */
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

        $callNo = 0;
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
                $storageHandlerMock->expects(self::at($callNo++))
                    ->method('storeFieldData')
                    ->with(
                        self::isInstanceOf(VersionInfo::class),
                        self::equalTo($field)
                    )->will(self::returnValue($storageHandlerUpdatesFields));
            }
        }

        /* @var $copyField */
        /* @var $originalField */
        $storageHandlerMock->expects(self::at($callNo))
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::equalTo($copyField),
                self::equalTo($originalField)
            )->will(self::returnValue($storageHandlerUpdatesFields));
    }

    public function testCreateNewFields()
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

    public function testCreateNewFieldsUpdatingStorageHandler()
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

        $callNo = 0;
        $fieldValue = new FieldValue();
        foreach ([1, 2, 3] as $fieldDefinitionId) {
            $field = new Field(
                [
                    'id' => 42,
                    'fieldDefinitionId' => $fieldDefinitionId,
                    'type' => 'some-type',
                    'versionNo' => 1,
                    'value' => $fieldValue,
                    'languageCode' => 'eng-GB',
                ]
            );
            $storageHandlerMock->expects(self::at($callNo++))
                ->method('storeFieldData')
                ->with(
                    self::isInstanceOf(VersionInfo::class),
                    self::equalTo($field)
                )->will(self::returnValue($storageHandlerUpdatesFields));
        }
    }

    public function testCreateNewFieldsForMainLanguage()
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

    public function testCreateNewFieldsForMainLanguageUpdatingStorageHandler()
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

        $callNo = 0;
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
                $storageHandlerMock->expects(self::at($callNo++))
                    ->method('copyFieldData')
                    ->with(
                        self::isInstanceOf(VersionInfo::class),
                        self::equalTo($field),
                        self::equalTo($originalField)
                    )->will(self::returnValue($storageHandlerUpdatesFields));
            }
        }
    }

    public function testCreateExistingFieldsInNewVersion()
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

    public function testCreateExistingFieldsInNewVersionUpdatingStorageHandler()
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

    public function testLoadExternalFieldData()
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

        $callNo = 0;
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
            $storageHandlerMock->expects(self::at($callNo++))
                ->method('storeFieldData')
                ->with(
                    self::isInstanceOf(VersionInfo::class),
                    self::equalTo($field)
                )->will(self::returnValue($storageHandlerUpdatesFields));
        }

        /* @var $copyField */
        /* @var $originalField */
        $storageHandlerMock->expects(self::at($callNo))
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::equalTo($copyField),
                self::equalTo($originalField)
            )->will(self::returnValue($storageHandlerUpdatesFields));
    }

    public function testUpdateFieldsWithNewLanguage()
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

    public function testUpdateFieldsWithNewLanguageUpdatingStorageHandler()
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

        $callNo = 0;
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
                    $storageHandlerMock->expects(self::at($callNo++))
                        ->method('storeFieldData')
                        ->with(
                            self::isInstanceOf(VersionInfo::class),
                            self::equalTo($field)
                        )->will(self::returnValue($storageHandlerUpdatesFields));
                }
            }
        }

        foreach ($fieldsToCopy as $fieldToCopy) {
            $storageHandlerMock->expects(self::at($callNo++))
                ->method('copyFieldData')
                ->with(
                    self::isInstanceOf(VersionInfo::class),
                    self::equalTo($fieldToCopy['copy']),
                    self::equalTo($fieldToCopy['original'])
                )->will(self::returnValue($storageHandlerUpdatesFields));
        }
    }

    public function testUpdateFieldsExistingLanguages()
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

    public function testUpdateFieldsExistingLanguagesUpdatingStorageHandler()
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

        $callNo = 0;
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
            $storageHandlerMock->expects(self::at($callNo++))
                ->method('storeFieldData')
                ->with(
                    self::isInstanceOf(VersionInfo::class),
                    self::equalTo($field)
                )->will(self::returnValue($storageHandlerUpdatesFields));
        }

        foreach ($fieldsToCopy as $fieldToCopy) {
            $storageHandlerMock->expects(self::at($callNo++))
                ->method('copyFieldData')
                ->with(
                    self::isInstanceOf(VersionInfo::class),
                    self::equalTo($fieldToCopy['copy']),
                    self::equalTo($fieldToCopy['original'])
                )->will(self::returnValue($storageHandlerUpdatesFields));
        }
    }

    public function testUpdateFieldsForInitialLanguage()
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

    public function testUpdateFieldsForInitialLanguageUpdatingStorageHandler()
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

    public function testDeleteFields()
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
                self::isType('string')
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
