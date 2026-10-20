<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct as SPILanguageCreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway as LanguageGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Handler;
use Ibexa\Core\Persistence\Legacy\Content\Language\Mapper as LanguageMapper;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Handler::class)]
class LanguageHandlerTest extends TestCase
{
    /**
     * Language handler.
     *
     * @var Handler
     */
    protected $languageHandler;

    /**
     * Language gateway mock.
     *
     * @var LanguageGateway
     */
    protected $gatewayMock;

    /**
     * Language mapper mock.
     *
     * @var LanguageMapper
     */
    protected $mapperMock;

    public function testCreate(): void
    {
        $handler = $this->getLanguageHandler();

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects($this->once())
            ->method('createLanguageFromCreateStruct')
            ->with(
                self::isInstanceOf(
                    SPILanguageCreateStruct::class
                )
            )->will($this->returnValue(new Language()));

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects($this->once())
            ->method('insertLanguage')
            ->with(
                self::isInstanceOf(
                    Language::class
                )
            )->will($this->returnValue(2));

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
     * @return SPILanguageCreateStruct
     */
    protected function getCreateStructFixture()
    {
        return new SPILanguageCreateStruct();
    }

    public function testUpdate(): void
    {
        $handler = $this->getLanguageHandler();

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects($this->once())
            ->method('updateLanguage')
            ->with(self::isInstanceOf(Language::class));

        $handler->update($this->getLanguageFixture());
    }

    /**
     * Returns a Language.
     *
     * @return Language
     */
    protected function getLanguageFixture()
    {
        return new Language();
    }

    public function testLoad(): void
    {
        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadLanguageListData')
            ->with(self::equalTo([2]))
            ->will($this->returnValue([]));

        $mapperMock->expects($this->once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            ->will($this->returnValue([new Language()]));

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

        $gatewayMock->expects($this->once())
            ->method('loadLanguageListData')
            ->with(self::equalTo([2]))
            ->will($this->returnValue([]));

        $mapperMock->expects($this->once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            // No language extracted
            ->will($this->returnValue([]));

        $result = $handler->load(2);
    }

    public function testLoadByLanguageCode(): void
    {
        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadLanguageListDataByLanguageCode')
            ->with(self::equalTo(['eng-US']))
            ->will($this->returnValue([]));

        $mapperMock->expects($this->once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            ->will($this->returnValue([new Language()]));

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

        $gatewayMock->expects($this->once())
            ->method('loadLanguageListDataByLanguageCode')
            ->with(self::equalTo(['eng-US']))
            ->will($this->returnValue([]));

        $mapperMock->expects($this->once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            // No language extracted
            ->will($this->returnValue([]));

        $result = $handler->loadByLanguageCode('eng-US');
    }

    public function testLoadAll(): void
    {
        $handler = $this->getLanguageHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadAllLanguagesData')
            ->will($this->returnValue([]));

        $mapperMock->expects($this->once())
            ->method('extractLanguagesFromRows')
            ->with(self::equalTo([]))
            ->will($this->returnValue([new Language()]));

        $result = $handler->loadAll();

        self::assertIsArray(
            $result
        );
    }

    public function testDeleteSuccess(): void
    {
        $handler = $this->getLanguageHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('canDeleteLanguage')
            ->with(self::equalTo(2))
            ->will($this->returnValue(true));
        $gatewayMock->expects($this->once())
            ->method('deleteLanguage')
            ->with(self::equalTo(2));

        $result = $handler->delete(2);
    }

    public function testDeleteFail(): void
    {
        $this->expectException(\LogicException::class);

        $handler = $this->getLanguageHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('canDeleteLanguage')
            ->with(self::equalTo(2))
            ->will($this->returnValue(false));
        $gatewayMock->expects($this->never())
            ->method('deleteLanguage');

        $result = $handler->delete(2);
    }

    /**
     * Returns the language handler to test.
     *
     * @return Handler
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
     * @return LanguageMapper
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
     * @return LanguageGateway
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(LanguageGateway::class);
        }

        return $this->gatewayMock;
    }
}
