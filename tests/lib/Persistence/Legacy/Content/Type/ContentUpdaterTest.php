<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;
use Ibexa\Core\Search\Legacy\Content\Handler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater
 */
class ContentUpdaterTest extends TestCase
{
    /**
     * Content gateway mock.
     *
     * @var Gateway
     */
    protected $contentGatewayMock;

    /**
     * FieldValue converter registry mock.
     *
     * @var ConverterRegistry
     */
    protected $converterRegistryMock;

    /**
     * Search handler mock.
     *
     * @var Handler
     */
    protected $searchHandlerMock;

    /**
     * Content StorageHandler mock.
     *
     * @var StorageHandler
     */
    protected $contentStorageHandlerMock;

    /**
     * Content Updater to test.
     *
     * @var ContentUpdater
     */
    protected $contentUpdater;

    /** @var Mapper */
    protected $contentMapperMock;

    public function testDetermineActions()
    {
        $fromType = $this->getFromTypeFixture();
        $toType = $this->getToTypeFixture();

        $converterRegMock = $this->getConverterRegistryMock();
        $converterRegMock->expects(self::once())
            ->method('getConverter')
            ->with('ibexa_string')
            ->will(
                self::returnValue(
                    ($converterMock = $this->createMock(Converter::class))
                )
            );

        $updater = $this->getContentUpdater();

        $actions = $updater->determineActions($fromType, $toType);

        self::assertEquals(
            [
                new Action\RemoveField(
                    $this->getContentGatewayMock(),
                    $fromType->fieldDefinitions[0],
                    $this->getContentStorageHandlerMock(),
                    $this->getContentMapperMock()
                ),
                new Action\AddField(
                    $this->getContentGatewayMock(),
                    $toType->fieldDefinitions[2],
                    $converterMock,
                    $this->getContentStorageHandlerMock(),
                    $this->getContentMapperMock()
                ),
            ],
            $actions
        );
    }

    public function testApplyUpdates()
    {
        $updater = $this->getContentUpdater();

        $actionA = $this->getMockForAbstractClass(
            Action::class,
            [],
            '',
            false
        );
        $actionACallCount = 0;
        $actionA->expects(self::exactly(2))
            ->method('apply')
            ->willReturnCallback(static function ($contentId) use (&$actionACallCount) {
                $expectedContentIds = [11, 22];
                self::assertEquals($expectedContentIds[$actionACallCount], $contentId);
                ++$actionACallCount;
            });
        $actionB = $this->getMockForAbstractClass(
            Action::class,
            [],
            '',
            false
        );
        $actionBCallCount = 0;
        $actionB->expects(self::exactly(2))
            ->method('apply')
            ->willReturnCallback(static function ($contentId) use (&$actionBCallCount) {
                $expectedContentIds = [11, 22];
                self::assertEquals($expectedContentIds[$actionBCallCount], $contentId);
                ++$actionBCallCount;
            });

        $actions = [$actionA, $actionB];

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('getContentIdsByContentTypeId')
            ->with(23)
            ->will(
                self::returnValue([11, 22])
            );

        $updater->applyUpdates(23, $actions);
    }

    /**
     * Returns a fixture for the from Type.
     *
     * @return Type
     */
    protected function getFromTypeFixture()
    {
        $type = new Type();

        $fieldA = new Type\FieldDefinition();
        $fieldA->id = 1;
        $fieldA->fieldType = 'ibexa_string';

        $fieldB = new Type\FieldDefinition();
        $fieldB->id = 2;
        $fieldB->fieldType = 'ibexa_string';

        $type->fieldDefinitions = [
            $fieldA, $fieldB,
        ];

        return $type;
    }

    /**
     * Returns a fixture for the to Type.
     *
     * @return Type
     */
    protected function getToTypeFixture()
    {
        $type = clone $this->getFromTypeFixture();

        unset($type->fieldDefinitions[0]);

        $fieldC = new Type\FieldDefinition();
        $fieldC->id = 3;
        $fieldC->fieldType = 'ibexa_string';

        $type->fieldDefinitions[] = $fieldC;

        return $type;
    }

    /**
     * Returns a Content Gateway mock.
     *
     * @return Gateway
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->createMock(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /**
     * Returns a FieldValue Converter registry mock.
     *
     * @return ConverterRegistry
     */
    protected function getConverterRegistryMock()
    {
        if (!isset($this->converterRegistryMock)) {
            $this->converterRegistryMock = $this->createMock(ConverterRegistry::class);
        }

        return $this->converterRegistryMock;
    }

    /**
     * Returns a Content StorageHandler mock.
     *
     * @return StorageHandler
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
     * @return Mapper
     */
    protected function getContentMapperMock()
    {
        if (!isset($this->contentMapperMock)) {
            $this->contentMapperMock = $this->createMock(Mapper::class);
        }

        return $this->contentMapperMock;
    }

    /**
     * Returns the content updater to test.
     *
     * @return ContentUpdater
     */
    protected function getContentUpdater()
    {
        if (!isset($this->contentUpdater)) {
            $this->contentUpdater = new ContentUpdater(
                $this->getContentGatewayMock(),
                $this->getConverterRegistryMock(),
                $this->getContentStorageHandlerMock(),
                $this->getContentMapperMock()
            );
        }

        return $this->contentUpdater;
    }
}
