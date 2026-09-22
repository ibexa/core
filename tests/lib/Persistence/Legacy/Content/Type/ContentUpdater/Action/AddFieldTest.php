<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 * Test case for content type Updater.
 */
#[CoversClass(ContentUpdater::class)]
class AddFieldTest extends TestCase
{
    /**
     * Content gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Gateway
     */
    protected $contentGatewayMock;

    /**
     * Content gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\StorageHandler
     */
    protected $contentStorageHandlerMock;

    /**
     * FieldValue converter mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter
     */
    protected $fieldValueConverterMock;

    /** @var \Ibexa\Core\Persistence\Legacy\Content\Mapper */
    protected $contentMapperMock;

    /**
     * AddField action to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField
     */
    protected $addFieldAction;

    public function testConstructor(): void
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

    public function testApplySingleVersionSingleTranslation(): void
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
            ->expects(self::once())
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

    public function testApplySingleVersionMultipleTranslations(): void
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
            ->expects(self::once())
            ->method('load')
            ->with($contentId, 1)
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will(self::returnValue([$content]));

        $matcher = self::exactly(2);
        $action
            ->expects($matcher)
            ->method('insertField')
            ->willReturnCallback(function (...$parameters) use ($matcher, $content) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertEquals([$content, $this->getFieldReference(null, 1, 'eng-GB')], $parameters);

                    return 'fieldId1';
                }

                self::assertEquals([$content, $this->getFieldReference(null, 1, 'ger-DE')], $parameters);

                return 'fieldId2';
            });

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsSingleTranslation(): void
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

        $loadMatcher = self::exactly(2);
        $this->getContentGatewayMock()
            ->expects($loadMatcher)
            ->method('load')
            ->willReturnCallback(static function (...$parameters) use ($loadMatcher, $contentId) {
                self::assertSame([$contentId, $loadMatcher->numberOfInvocations(), null], $parameters);

                return [];
            });

        $extractMatcher = self::exactly(2);
        $this->getContentMapperMock()
            ->expects($extractMatcher)
            ->method('extractContentFromRows')
            ->willReturnCallback(static function (...$parameters) use ($extractMatcher, $content1, $content2) {
                self::assertSame([[], [], 'content_', null], $parameters);

                return $extractMatcher->numberOfInvocations() === 1 ? [$content1] : [$content2];
            });

        $insertMatcher = self::exactly(2);
        $action
            ->expects($insertMatcher)
            ->method('insertField')
            ->willReturnCallback(function (...$parameters) use ($insertMatcher, $content1, $content2) {
                if ($insertMatcher->numberOfInvocations() === 1) {
                    self::assertEquals([$content1, $this->getFieldReference(null, 1, 'eng-GB')], $parameters);
                } else {
                    self::assertEquals([$content2, $this->getFieldReference('fieldId1', 2, 'eng-GB')], $parameters);
                }

                return 'fieldId1';
            });

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsMultipleTranslations(): void
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

        $loadMatcher = self::exactly(2);
        $this->getContentGatewayMock()
            ->expects($loadMatcher)
            ->method('load')
            ->willReturnCallback(static function (...$parameters) use ($loadMatcher, $contentId) {
                self::assertSame([$contentId, $loadMatcher->numberOfInvocations(), null], $parameters);

                return [];
            });

        $extractMatcher = self::exactly(2);
        $this->getContentMapperMock()
            ->expects($extractMatcher)
            ->method('extractContentFromRows')
            ->willReturnCallback(static function (...$parameters) use ($extractMatcher, $content1, $content2) {
                self::assertSame([[], [], 'content_', null], $parameters);

                return $extractMatcher->numberOfInvocations() === 1 ? [$content1] : [$content2];
            });

        $insertMatcher = self::exactly(4);
        $action
            ->expects($insertMatcher)
            ->method('insertField')
            ->willReturnCallback(function (...$parameters) use ($insertMatcher, $content1, $content2) {
                switch ($insertMatcher->numberOfInvocations()) {
                    case 1:
                        self::assertEquals([$content1, $this->getFieldReference(null, 1, 'eng-GB')], $parameters);

                        return 'fieldId1';
                    case 2:
                        self::assertEquals([$content1, $this->getFieldReference(null, 1, 'ger-DE')], $parameters);

                        return 'fieldId2';
                    case 3:
                        self::assertEquals([$content2, $this->getFieldReference('fieldId1', 2, 'eng-GB')], $parameters);

                        return 'fieldId1';
                    default:
                        self::assertEquals([$content2, $this->getFieldReference('fieldId2', 2, 'ger-DE')], $parameters);

                        return 'fieldId2';
                }
            });

        $action->apply($contentId);
    }

    public function testInsertNewField(): void
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

    public function testInsertNewFieldUpdating(): void
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

    public function testInsertExistingField(): void
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

    public function testInsertExistingFieldUpdating(): void
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
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentFixture($versionNo, array $languageCodes)
    {
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Gateway
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\StorageHandler
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Mapper
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
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition
     */
    protected function getFieldDefinitionFixture()
    {
        $fieldDef = new Content\Type\FieldDefinition();
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
     * @return \Ibexa\Contracts\Core\Persistence\Content\Field
     */
    public function getFieldReference($id, $versionNo, $languageCode)
    {
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField
     */
    protected function getMockedAction($methods = [])
    {
        $builder = $this
            ->getMockBuilder(AddField::class)
            ->setConstructorArgs(
                [
                    $this->getContentGatewayMock(),
                    $this->getFieldDefinitionFixture(),
                    $this->getFieldValueConverterMock(),
                    $this->getContentStorageHandlerMock(),
                    $this->getContentMapperMock(),
                ]
            );

        if ($methods !== []) {
            $builder->onlyMethods($methods);
        }

        return $builder->getMock();
    }
}
