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

    /** @var ContentMapper */
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

        $this->assertInstanceOf(AddField::class, $action);
    }

    public function testApplySingleVersionSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $content = $this->getContentFixture(1, ['cro-HR']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('load')
            ->with($contentId, 1)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content]));

        $action
            ->expects($this->once())
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'cro-HR'))
            ->will($this->returnValue('fieldId1'));

        $action->apply($contentId);
    }

    public function testApplySingleVersionMultipleTranslations()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $content = $this->getContentFixture(1, ['eng-GB', 'ger-DE']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('load')
            ->with($contentId, 1)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content]));

        $action
            ->expects($this->exactly(2))
            ->method('insertField')
            ->withConsecutive(
                [$content, $this->getFieldReference(null, 1, 'eng-GB')],
                [$content, $this->getFieldReference(null, 1, 'ger-DE')]
            )
            ->willReturnOnConsecutiveCalls('fieldId1', 'fieldId2');

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
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$contentId, 1], [$contentId, 2])
            ->willReturnOnConsecutiveCalls([], []);

        $this->getContentMapperMock()
            ->expects($this->exactly(2))
            ->method('extractContentFromRows')
            ->withConsecutive([[], []], [[], []])
            ->willReturnOnConsecutiveCalls([$content1], [$content2]);

        $action
            ->expects($this->exactly(2))
            ->method('insertField')
            ->withConsecutive(
                [$content1, $this->getFieldReference(null, 1, 'eng-GB')],
                [$content2, $this->getFieldReference('fieldId1', 2, 'eng-GB')]
            )
            ->willReturnOnConsecutiveCalls('fieldId1', 'fieldId1');

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
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$contentId, 1], [$contentId, 2])
            ->willReturnOnConsecutiveCalls([], []);

        $this->getContentMapperMock()
            ->expects($this->exactly(2))
            ->method('extractContentFromRows')
            ->withConsecutive([[], []], [[], []])
            ->willReturnOnConsecutiveCalls([$content1], [$content2]);

        $action
            ->expects($this->exactly(4))
            ->method('insertField')
            ->withConsecutive(
                [$content1, $this->getFieldReference(null, 1, 'eng-GB')],
                [$content1, $this->getFieldReference(null, 1, 'ger-DE')],
                [$content2, $this->getFieldReference('fieldId1', 2, 'eng-GB')],
                [$content2, $this->getFieldReference('fieldId2', 2, 'ger-DE')]
            )
            ->willReturnOnConsecutiveCalls('fieldId1', 'fieldId2', 'fieldId1', 'fieldId2');

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
            ->expects($this->once())
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertNewField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            )
            ->will($this->returnValue(23));

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(false));

        $this->getContentGatewayMock()->expects($this->never())->method('updateField');

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(23, $fieldId);
        $this->assertEquals(23, $field->id);
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
            ->expects($this->exactly(2))
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertNewField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            )
            ->will($this->returnValue(23));

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(true));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('updateField')
            ->with(
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(23, $fieldId);
        $this->assertEquals(23, $field->id);
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
            ->expects($this->once())
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertExistingField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(false));

        $this->getContentGatewayMock()->expects($this->never())->method('updateField');

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(32, $fieldId);
        $this->assertEquals(32, $field->id);
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
            ->expects($this->exactly(2))
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertExistingField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(true));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('updateField')
            ->with(
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(32, $fieldId);
        $this->assertEquals(32, $field->id);
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
     * @return MockObject|ContentMapper
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
        $fieldDef->fieldType = 'ezstring';
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
        $field->type = 'ezstring';
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

class_alias(AddFieldTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\Type\ContentUpdater\Action\AddFieldTest');
