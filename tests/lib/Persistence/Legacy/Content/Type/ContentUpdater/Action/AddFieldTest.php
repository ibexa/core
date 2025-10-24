<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 * Test case for content type Updater.
 */
class AddFieldTest extends TestCase
{
    /**
     * Content gateway mock.
     *
     * @var Gateway
     */
    protected $contentGatewayMock;

    /**
     * Content gateway mock.
     *
     * @var StorageHandler
     */
    protected $contentStorageHandlerMock;

    /**
     * FieldValue converter mock.
     *
     * @var Converter
     */
    protected $fieldValueConverterMock;

    /** @var Mapper */
    protected $contentMapperMock;

    /**
     * AddField action to test.
     *
     * @var AddField
     */
    protected $addFieldAction;

    /**
     * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater::__construct
     */
    public function testConstructor()
    {
        $action = new AddField(
            $this->getContentGatewayMock(),
            $this->getFieldDefinitionFixture(),
            $this->getFieldValueConverterMock(),
            $this->getContentStorageHandlerMock(),
            $this->getContentMapperMock()
        );

        self::assertInstanceOf(AddField::class, $action);
    }

    public function testApplySingleVersionSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $content = $this->getContentFixture(1, ['cro-HR']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('listVersionNumbers')
            ->with(self::equalTo($contentId))
            ->will(self::returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => $contentId, 'version' => 1]]))
            ->will(self::returnValue([]));

        $this->getContentGatewayMock()
            ->expects(self::at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content]));

        $action
            ->expects(self::once())
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'cro-HR'))
            ->will(self::returnValue('fieldId1'));

        $action->apply($contentId);
    }

    public function testApplySingleVersionMultipleTranslations()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $content = $this->getContentFixture(1, ['eng-GB', 'ger-DE']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('listVersionNumbers')
            ->with(self::equalTo($contentId))
            ->will(self::returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => $contentId, 'version' => 1]]))
            ->will(self::returnValue([]));

        $this->getContentGatewayMock()
            ->expects(self::at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content]));

        $action
            ->expects(self::at(0))
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'eng-GB'))
            ->will(self::returnValue('fieldId1'));

        $action
            ->expects(self::at(1))
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'ger-DE'))
            ->will(self::returnValue('fieldId2'));

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $content1 = $this->getContentFixture(1, ['eng-GB']);
        $content2 = $this->getContentFixture(2, ['eng-GB']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('listVersionNumbers')
            ->with(self::equalTo($contentId))
            ->will(self::returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]]))
            ->will(self::returnValue([]));

        $this->getContentGatewayMock()
            ->expects(self::at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::at(0))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content1]));

        $this->getContentGatewayMock()
            ->expects(self::at(3))
            ->method('load')
            ->with($contentId, 2)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::at(1))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content2]));

        $action
            ->expects(self::at(0))
            ->method('insertField')
            ->with($content1, $this->getFieldReference(null, 1, 'eng-GB'))
            ->will(self::returnValue('fieldId1'));

        $action
            ->expects(self::at(1))
            ->method('insertField')
            ->with($content2, $this->getFieldReference('fieldId1', 2, 'eng-GB'))
            ->will(self::returnValue('fieldId1'));

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsMultipleTranslations()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $content1 = $this->getContentFixture(1, ['eng-GB', 'ger-DE']);
        $content2 = $this->getContentFixture(2, ['eng-GB', 'ger-DE']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('listVersionNumbers')
            ->with(self::equalTo($contentId))
            ->will(self::returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]]))
            ->will(self::returnValue([]));

        $this->getContentGatewayMock()
            ->expects(self::at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::at(0))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content1]));

        $this->getContentGatewayMock()
            ->expects(self::at(3))
            ->method('load')
            ->with($contentId, 2)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::at(1))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content2]));

        $action
            ->expects(self::at(0))
            ->method('insertField')
            ->with($content1, $this->getFieldReference(null, 1, 'eng-GB'))
            ->will(self::returnValue('fieldId1'));

        $action
            ->expects(self::at(1))
            ->method('insertField')
            ->with($content1, $this->getFieldReference(null, 1, 'ger-DE'))
            ->will(self::returnValue('fieldId2'));

        $action
            ->expects(self::at(2))
            ->method('insertField')
            ->with($content2, $this->getFieldReference('fieldId1', 2, 'eng-GB'))
            ->will(self::returnValue('fieldId1'));

        $action
            ->expects(self::at(3))
            ->method('insertField')
            ->with($content2, $this->getFieldReference('fieldId2', 2, 'ger-DE'))
            ->will(self::returnValue('fieldId2'));

        $action->apply($contentId);
    }

    public function testInsertNewField()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = null;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects(self::once())
            ->method('toStorageValue')
            ->with(
                $value,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('insertNewField')
            ->with(
                $content,
                $field,
                self::isInstanceOf(StorageFieldValue::class)
            )
            ->will(self::returnValue(23));

        $this->getContentStorageHandlerMock()
            ->expects(self::once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will(self::returnValue(false));

        $this->getContentGatewayMock()->expects(self::never())->method('updateField');

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        self::assertEquals(23, $fieldId);
        self::assertEquals(23, $field->id);
    }

    public function testInsertNewFieldUpdating()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = null;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects(self::exactly(2))
            ->method('toStorageValue')
            ->with(
                $value,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('insertNewField')
            ->with(
                $content,
                $field,
                self::isInstanceOf(StorageFieldValue::class)
            )
            ->will(self::returnValue(23));

        $this->getContentStorageHandlerMock()
            ->expects(self::once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will(self::returnValue(true));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('updateField')
            ->with(
                $field,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        self::assertEquals(23, $fieldId);
        self::assertEquals(23, $field->id);
    }

    public function testInsertExistingField()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = 32;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects(self::once())
            ->method('toStorageValue')
            ->with(
                $value,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('insertExistingField')
            ->with(
                $content,
                $field,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentStorageHandlerMock()
            ->expects(self::once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will(self::returnValue(false));

        $this->getContentGatewayMock()->expects(self::never())->method('updateField');

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        self::assertEquals(32, $fieldId);
        self::assertEquals(32, $field->id);
    }

    public function testInsertExistingFieldUpdating()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = 32;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects(self::exactly(2))
            ->method('toStorageValue')
            ->with(
                $value,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('insertExistingField')
            ->with(
                $content,
                $field,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentStorageHandlerMock()
            ->expects(self::once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will(self::returnValue(true));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('updateField')
            ->with(
                $field,
                self::isInstanceOf(StorageFieldValue::class)
            );

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        self::assertEquals(32, $fieldId);
        self::assertEquals(32, $field->id);
    }

    /**
     * Returns a Content fixture.
     *
     * @param int $versionNo
     * @param array $languageCodes
     *
     * @return Content
     */
    protected function getContentFixture(
        $versionNo,
        array $languageCodes
    ) {
        $contentInfo = new Content\ContentInfo();
        $contentInfo->id = 'contentId';
        $versionInfo = new Content\VersionInfo();
        $versionInfo->contentInfo = $contentInfo;

        $content = new Content();
        $content->versionInfo = $versionInfo;
        $content->versionInfo->versionNo = $versionNo;

        $fields = [];
        foreach ($languageCodes as $languageCode) {
            $fields[] = new Field(['languageCode' => $languageCode]);
        }

        $content->fields = $fields;

        return $content;
    }

    /**
     * Returns a Content Gateway mock.
     *
     * @return MockObject|Gateway
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->createMock(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /**
     * Returns a FieldValue converter mock.
     *
     * @return MockObject|Converter
     */
    protected function getFieldValueConverterMock()
    {
        if (!isset($this->fieldValueConverterMock)) {
            $this->fieldValueConverterMock = $this->createMock(Converter::class);
        }

        return $this->fieldValueConverterMock;
    }

    /**
     * Returns a Content StorageHandler mock.
     *
     * @return MockObject|StorageHandler
     */
    protected function getContentStorageHandlerMock()
    {
        if (!isset($this->contentStorageHandlerMock)) {
            $this->contentStorageHandlerMock = $this->createMock(StorageHandler::class);
        }

        return $this->contentStorageHandlerMock;
    }

    /**
     * Returns a Content mapper mock.
     *
     * @return MockObject|Mapper
     */
    protected function getContentMapperMock()
    {
        if (!isset($this->contentMapperMock)) {
            $this->contentMapperMock = $this->createMock(ContentMapper::class);
        }

        return $this->contentMapperMock;
    }

    /**
     * Returns a FieldDefinition fixture.
     *
     * @return FieldDefinition
     */
    protected function getFieldDefinitionFixture()
    {
        $fieldDef = new FieldDefinition();
        $fieldDef->id = 42;
        $fieldDef->isTranslatable = true;
        $fieldDef->fieldType = 'ibexa_string';
        $fieldDef->defaultValue = new Content\FieldValue();

        return $fieldDef;
    }

    /**
     * Returns a reference Field.
     *
     * @param int $id
     * @param int $versionNo
     * @param string $languageCode
     *
     * @return Field
     */
    public function getFieldReference(
        $id,
        $versionNo,
        $languageCode
    ) {
        $field = new Field();

        $field->id = $id;
        $field->fieldDefinitionId = 42;
        $field->type = 'ibexa_string';
        $field->value = new Content\FieldValue();
        $field->versionNo = $versionNo;
        $field->languageCode = $languageCode;

        return $field;
    }

    /**
     * @param $methods
     *
     * @return MockObject|AddField
     */
    protected function getMockedAction($methods = [])
    {
        return $this
            ->getMockBuilder(AddField::class)
            ->setMethods((array)$methods)
            ->setConstructorArgs(
                [
                    $this->getContentGatewayMock(),
                    $this->getFieldDefinitionFixture(),
                    $this->getFieldValueConverterMock(),
                    $this->getContentStorageHandlerMock(),
                    $this->getContentMapperMock(),
                ]
            )
            ->getMock();
    }
}
