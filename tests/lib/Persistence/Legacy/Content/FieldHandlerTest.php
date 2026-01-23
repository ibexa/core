<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\FieldType\FieldType;
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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\FieldHandler
 */
class FieldHandlerTest extends LanguageAwareTestCase
{
    /**
     * Gateway mock.
     *
     * @var Gateway
     */
    protected $contentGatewayMock;

    /**
     * Mapper mock.
     *
     * @var Mapper
     */
    protected $mapperMock;

    /**
     * Storage handler mock.
     *
     * @var StorageHandler
     */
    protected $storageHandlerMock;

    /**
     * Field type registry mock.
     *
     * @var FieldTypeRegistry
     */
    protected $fieldTypeRegistryMock;

    /**
     * Field type mock.
     *
     * @var FieldType
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

        $fieldValue = new FieldValue();
        $expectedCopyField = new Field(
            [
                'id' => 42,
                'fieldDefinitionId' => 2,
                'type' => 'some-type',
                'versionNo' => 1,
                'value' => $fieldValue,
                'languageCode' => 'eng-US',
            ]
        );
        $expectedOriginalField = clone $expectedCopyField;
        $expectedOriginalField->languageCode = 'eng-GB';

        $storageHandlerMock->expects(self::exactly(5))
            ->method('storeFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));

        $storageHandlerMock->expects(self::once())
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::equalTo($expectedCopyField),
                self::equalTo($expectedOriginalField)
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

        $storageHandlerMock->expects(self::exactly(3))
            ->method('storeFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));
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

        $storageHandlerMock->expects(self::exactly(6))
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));
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

        $storageHandlerMock->expects(self::exactly(2))
            ->method('storeFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));

        $fieldValue = new FieldValue();
        $expectedCopyField = new Field(
            [
                'fieldDefinitionId' => 3,
                'type' => 'some-type',
                'versionNo' => 1,
                'value' => $fieldValue,
                'languageCode' => 'ger-DE',
            ]
        );
        $expectedOriginalField = clone $expectedCopyField;
        $expectedOriginalField->id = 32;
        $expectedOriginalField->languageCode = 'eng-GB';

        $storageHandlerMock->expects(self::once())
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::equalTo($expectedCopyField),
                self::equalTo($expectedOriginalField)
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

        // 4 fields are stored (fieldDef 1 in both languages, and fieldDef 2 and 3 in eng-GB)
        $storageHandlerMock->expects(self::exactly(4))
            ->method('storeFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));

        // 2 fields are copied (fieldDef 2 and 3 for eng-US, copied from eng-GB)
        $storageHandlerMock->expects(self::exactly(2))
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));
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

        // Only fieldDef 1 is stored (as empty) - fieldDef 2 and 3 are copied from main language
        $storageHandlerMock->expects(self::once())
            ->method('storeFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));

        // fieldDef 2 and 3 are copied from eng-GB to eng-US
        $storageHandlerMock->expects(self::exactly(2))
            ->method('copyFieldData')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isInstanceOf(Field::class),
                self::isInstanceOf(Field::class)
            )->will(self::returnValue($storageHandlerUpdatesFields));
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
     * @return Content
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
     * @return Content
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
     * @return Content
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
     * @return Content
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
     * @return Type
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
     * @return UpdateStruct
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
     * @return FieldHandler
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
     * @return StorageHandler|MockObject
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
     * @return Mapper|MockObject
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
     * @return Gateway|MockObject
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /**
     * @return FieldTypeRegistry|MockObject
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
     * @return SPIFieldType|MockObject
     */
    protected function getFieldTypeMock()
    {
        if (!isset($this->fieldTypeMock)) {
            $this->fieldTypeMock = $this->createMock(SPIFieldType::class);
        }

        return $this->fieldTypeMock;
    }
}
