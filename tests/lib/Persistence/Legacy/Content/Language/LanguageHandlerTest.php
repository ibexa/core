<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct as SPILanguageCreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway as LanguageGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Handler;
use Ibexa\Core\Persistence\Legacy\Content\Language\Mapper as LanguageMapper;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Language\Handler
 */
class LanguageHandlerTest extends TestCase
{
    /**
     * Language handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\Handler
     */
    protected $languageHandler;

    /**
     * Language gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway
     */
    protected ?MockObject $gatewayMock = null;

    /**
     * Language mapper mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\Mapper
     */
    protected ?MockObject $mapperMock = null;

    public function testCreate(): void
    {
        $handler = $this->getLanguageHandler();

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('createLanguageFromCreateStruct')
            ->with(
                self::isInstanceOf(
                    SPILanguageCreateStruct::class
                )
            )->will(self::returnValue(new Language()));

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('insertLanguage')
            ->with(
                self::isInstanceOf(
                    Language::class
                )
            )->will(self::returnValue(2));

        $createStruct = $this->getCreateStructFixture();

        $result = $handler->create($createStruct);

        self::assertInstanceOf(
            Language::class,
            $result
        );
        self::assertEquals(
            2,
            $result->id
        );
    }

    /**
     * Returns a Language CreateStruct.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct
     */
    protected function getCreateStructFixture(): CreateStruct
    {
        return new CreateStruct();
    }

    public function testUpdate(): void
    {
        $handler = $this->getLanguageHandler();

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('updateLanguage')
            ->with(self::isInstanceOf(Language::class));

        $handler->update($this->getLanguageFixture());
    }

    /**
     * Returns a Language.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    protected function getLanguageFixture(): Language
    {
        return new Language();
    }

    public function testLoad(): void
    {
        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadLanguageListData')
            ->with(self::equalTo([2]))
            ->will(self::returnValue([]));

        $mapperMock->expects(self::once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Language()]));

        $result = $handler->load(2);

        self::assertInstanceOf(
            Language::class,
            $result
        );
    }

    public function testLoadFailure(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadLanguageListData')
            ->with(self::equalTo([2]))
            ->will(self::returnValue([]));

        $mapperMock->expects(self::once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            // No language extracted
            ->will(self::returnValue([]));

        $result = $handler->load(2);
    }

    public function testLoadByLanguageCode(): void
    {
        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadLanguageListDataByLanguageCode')
            ->with(self::equalTo(['eng-US']))
            ->will(self::returnValue([]));

        $mapperMock->expects(self::once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Language()]));

        $result = $handler->loadByLanguageCode('eng-US');

        self::assertInstanceOf(
            Language::class,
            $result
        );
    }

    public function testLoadByLanguageCodeFailure(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadLanguageListDataByLanguageCode')
            ->with(self::equalTo(['eng-US']))
            ->will(self::returnValue([]));

        $mapperMock->expects(self::once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            // No language extracted
            ->will(self::returnValue([]));

        $result = $handler->loadByLanguageCode('eng-US');
    }

    public function testLoadAll(): void
    {
        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadAllLanguagesData')
            ->will(self::returnValue([]));

        $mapperMock->expects(self::once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Language()]));

        $result = $handler->loadAll();

        self::assertIsArray(
            $result
        );
    }

    public function testDeleteSuccess(): void
    {
        $handler = $this->getLanguageHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('canDeleteLanguage')
            ->with(self::equalTo(2))
            ->will(self::returnValue(true));
        $gatewayMock->expects(self::once())
            ->method('deleteLanguage')
            ->with(self::equalTo(2));

        $result = $handler->delete(2);
    }

    public function testDeleteFail(): void
    {
        $this->expectException(\LogicException::class);

        $handler = $this->getLanguageHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('canDeleteLanguage')
            ->with(self::equalTo(2))
            ->will(self::returnValue(false));
        $gatewayMock->expects(self::never())
            ->method('deleteLanguage');

        $result = $handler->delete(2);
    }

    /**
     * Returns the language handler to test.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Language\Handler
     */
    protected function getLanguageHandler()
    {
        if (!isset($this->languageHandler)) {
            $this->languageHandler = new Handler(
                $this->getGatewayMock(),
                $this->getMapperMock()
            );
        }

        return $this->languageHandler;
    }

    /**
     * Returns a language mapper mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Language\Mapper
     */
    protected function getMapperMock()
    {
        if (!isset($this->mapperMock)) {
            $this->mapperMock = $this->createMock(LanguageMapper::class);
        }

        return $this->mapperMock;
    }

    /**
     * Returns a mock for the language gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(LanguageGateway::class);
        }

        return $this->gatewayMock;
    }
}
