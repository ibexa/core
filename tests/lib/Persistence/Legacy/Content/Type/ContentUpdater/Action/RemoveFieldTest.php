<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\RemoveField;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
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

    /** @var ContentMapper */
    protected $contentMapperMock;

    /**
     * RemoveField action to test.
     *
     * @var RemoveField
     */
    protected $removeFieldAction;

    /**
     * Returns a callback verifying the arguments of consecutive calls of $method and returning the matching result.
     * Every call is appended to $calls, so the order between different methods of one mock can be asserted.
     *
     * @param list<string> $calls
     * @param array<int, array<int, mixed>> $expectedArguments per call, constraints or values compared with equalTo
     * @param array<int, mixed> $results per call
     */
    private static function sequence(
        array &$calls,
        string $method,
        array $expectedArguments,
        array $results = []
    ): \Closure {
        $callNo = 0;

        return static function (...$arguments) use (&$calls, &$callNo, $method, $expectedArguments, $results) {
            $calls[] = $method;
            foreach ($expectedArguments[$callNo] ?? [] as $position => $expected) {
                self::assertThat(
                    $arguments[$position],
                    $expected instanceof Constraint ? $expected : new IsEqual($expected)
                );
            }

            return $results[$callNo++] ?? null;
        };
    }

    public function testApplySingleVersionSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $action = $this->getRemoveFieldAction();
        $fieldId = 3;
        $content = $this->getContentFixture(1, ['cro-HR' => $fieldId]);

        $gatewayCalls = [];

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'listVersionNumbers', [[$this->equalTo($contentId)]], [$versionNumbers])
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'loadVersionedNameData', [[$this->equalTo([['id' => $contentId, 'version' => 1]])]], [[]])
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('load')
            ->willReturnCallback(self::sequence($gatewayCalls, 'load', [[$contentId, 1]], [[]]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content]));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('deleteField')
            ->with($this->equalTo($fieldId));

        $this->getContentStorageHandlerMock()->expects($this->once())
            ->method('deleteFieldData')
            ->with(
                $this->equalTo('ezstring'),
                $content->versionInfo,
                $this->equalTo([$fieldId])
            );

        $action->apply($contentId);

        self::assertSame(['listVersionNumbers', 'loadVersionedNameData', 'load'], $gatewayCalls);
    }

    public function testApplyMultipleVersionsSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $action = $this->getRemoveFieldAction();
        $fieldId = 3;
        $content1 = $this->getContentFixture(1, ['cro-HR' => $fieldId]);
        $content2 = $this->getContentFixture(2, ['cro-HR' => $fieldId]);

        $gatewayCalls = [];

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'listVersionNumbers', [[$this->equalTo($contentId)]], [$versionNumbers])
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'loadVersionedNameData', [[$this->equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]])]], [[]])
            );

        $this->getContentGatewayMock()
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(self::sequence($gatewayCalls, 'load', [[$contentId, 1], [$contentId, 2]], [[], []]));

        $this->getContentMapperMock()
            ->expects($this->exactly(2))
            ->method('extractContentFromRows')
            ->withConsecutive([[], []], [[], []])
            ->willReturnOnConsecutiveCalls([$content1], [$content2]);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('deleteField')
            ->with($this->equalTo($fieldId));

        $this->getContentStorageHandlerMock()
            ->expects($this->exactly(2))
            ->method('deleteFieldData')
            ->withConsecutive(
                ['ezstring', $content1->versionInfo, [$fieldId]],
                ['ezstring', $content2->versionInfo, [$fieldId]]
            );

        $action->apply($contentId);

        self::assertSame(['listVersionNumbers', 'loadVersionedNameData', 'load', 'load'], $gatewayCalls);
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

        $gatewayCalls = [];

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'listVersionNumbers', [[$this->equalTo($contentId)]], [$versionNumbers])
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'loadVersionedNameData', [[$this->equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]])]], [[]])
            );

        $this->getContentGatewayMock()
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(self::sequence($gatewayCalls, 'load', [[$contentId, 1], [$contentId, 2]], [[], []]));

        $this->getContentMapperMock()
            ->expects($this->exactly(2))
            ->method('extractContentFromRows')
            ->withConsecutive([[], []], [[], []])
            ->willReturnOnConsecutiveCalls([$content1], [$content2]);

        $this->getContentGatewayMock()
            ->expects($this->exactly(2))
            ->method('deleteField')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'deleteField', [[$this->equalTo($fieldId1)], [$this->equalTo($fieldId2)]])
            );

        $this->getContentStorageHandlerMock()
            ->expects($this->exactly(2))
            ->method('deleteFieldData')
            ->withConsecutive(
                ['ezstring', $content1->versionInfo, [$fieldId1, $fieldId2]],
                ['ezstring', $content2->versionInfo, [$fieldId1, $fieldId2]]
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('removeRelationsByFieldDefinitionId')
            ->willReturnCallback(
                self::sequence($gatewayCalls, 'removeRelationsByFieldDefinitionId', [[$this->equalTo(42)]])
            );

        $action->apply($contentId);

        self::assertSame(['listVersionNumbers', 'loadVersionedNameData', 'load', 'load', 'removeRelationsByFieldDefinitionId', 'deleteField', 'deleteField'], $gatewayCalls);
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
            $fieldNoRemove->type = 'ezstring';
            $fieldNoRemove->languageCode = $languageCode;

            $fields[] = $fieldNoRemove;

            $fieldRemove = new Content\Field();
            $fieldRemove->id = $fieldId;
            $fieldRemove->versionNo = $versionNo;
            $fieldRemove->fieldDefinitionId = 42;
            $fieldRemove->type = 'ezstring';
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
        $fieldDef->fieldType = 'ezstring';
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

class_alias(RemoveFieldTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\Type\ContentUpdater\Action\RemoveFieldTest');
