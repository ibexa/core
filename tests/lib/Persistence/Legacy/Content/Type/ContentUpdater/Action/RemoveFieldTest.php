<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\RemoveField;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\RemoveField
 */
class RemoveFieldTest extends TestCase
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

    /** @var Mapper */
    protected $contentMapperMock;

    /**
     * RemoveField action to test.
     *
     * @var RemoveField
     */
    protected $removeFieldAction;

    public function testApplySingleVersionSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $action = $this->getRemoveFieldAction();
        $fieldId = 3;
        $content = $this->getContentFixture(1, ['cro-HR' => $fieldId]);

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

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('deleteField')
            ->with(self::equalTo($fieldId));

        $this->getContentStorageHandlerMock()->expects(self::once())
            ->method('deleteFieldData')
            ->with(
                self::equalTo('ibexa_string'),
                $content->versionInfo,
                self::equalTo([$fieldId])
            );

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $action = $this->getRemoveFieldAction();
        $fieldId = 3;
        $content1 = $this->getContentFixture(1, ['cro-HR' => $fieldId]);
        $content2 = $this->getContentFixture(2, ['cro-HR' => $fieldId]);

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

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('deleteField')
            ->with(self::equalTo($fieldId));

        $this->getContentStorageHandlerMock()
            ->expects(self::at(0))
            ->method('deleteFieldData')
            ->with(
                self::equalTo('ibexa_string'),
                $content1->versionInfo,
                self::equalTo([$fieldId])
            );

        $this->getContentStorageHandlerMock()
            ->expects(self::at(1))
            ->method('deleteFieldData')
            ->with(
                self::equalTo('ibexa_string'),
                $content2->versionInfo,
                self::equalTo([$fieldId])
            );

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsMultipleTranslations()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $action = $this->getRemoveFieldAction();
        $fieldId1 = 3;
        $fieldId2 = 4;
        $content1 = $this->getContentFixture(1, ['cro-HR' => $fieldId1, 'hun-HU' => $fieldId2]);
        $content2 = $this->getContentFixture(2, ['cro-HR' => $fieldId1, 'hun-HU' => $fieldId2]);

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

        $this->getContentGatewayMock()
            ->expects(self::at(5))
            ->method('deleteField')
            ->with(self::equalTo($fieldId1));

        $this->getContentGatewayMock()
            ->expects(self::at(6))
            ->method('deleteField')
            ->with(self::equalTo($fieldId2));

        $this->getContentStorageHandlerMock()
            ->expects(self::at(0))
            ->method('deleteFieldData')
            ->with(
                self::equalTo('ibexa_string'),
                $content1->versionInfo,
                self::equalTo([$fieldId1, $fieldId2])
            );

        $this->getContentStorageHandlerMock()
            ->expects(self::at(1))
            ->method('deleteFieldData')
            ->with(
                self::equalTo('ibexa_string'),
                $content2->versionInfo,
                self::equalTo([$fieldId1, $fieldId2])
            );

        $this->getContentGatewayMock()
            ->expects(self::at(4))
            ->method('removeRelationsByFieldDefinitionId')
            ->with(self::equalTo(42));

        $action->apply($contentId);
    }

    protected function getContentFixture(
        int $versionNo,
        array $languageCodes
    ): Content {
        $fields = [];

        foreach ($languageCodes as $languageCode => $fieldId) {
            $fieldNoRemove = new Content\Field();
            $fieldNoRemove->id = 2;
            $fieldNoRemove->versionNo = $versionNo;
            $fieldNoRemove->fieldDefinitionId = 23;
            $fieldNoRemove->type = 'ibexa_string';
            $fieldNoRemove->languageCode = $languageCode;

            $fields[] = $fieldNoRemove;

            $fieldRemove = new Content\Field();
            $fieldRemove->id = $fieldId;
            $fieldRemove->versionNo = $versionNo;
            $fieldRemove->fieldDefinitionId = 42;
            $fieldRemove->type = 'ibexa_string';
            $fieldRemove->languageCode = $languageCode;

            $fields[] = $fieldRemove;
        }

        $content = new Content();
        $content->versionInfo = new Content\VersionInfo();
        $content->fields = $fields;
        $content->versionInfo->versionNo = $versionNo;

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
        $fieldDef->fieldType = 'ibexa_string';
        $fieldDef->defaultValue = new Content\FieldValue();

        return $fieldDef;
    }

    /**
     * Returns the RemoveField action to test.
     *
     * @return RemoveField
     */
    protected function getRemoveFieldAction()
    {
        if (!isset($this->removeFieldAction)) {
            $this->removeFieldAction = new RemoveField(
                $this->getContentGatewayMock(),
                $this->getFieldDefinitionFixture(),
                $this->getContentStorageHandlerMock(),
                $this->getContentMapperMock()
            );
        }

        return $this->removeFieldAction;
    }
}
