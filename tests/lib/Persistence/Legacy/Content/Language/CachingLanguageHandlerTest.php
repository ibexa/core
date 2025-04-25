<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct as SPILanguageCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as SPILanguageHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;
use Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler
 */
class CachingLanguageHandlerTest extends TestCase
{
    /**
     * Language handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\Handler
     */
    protected $languageHandler;

    /**
     * Inner language handler mock.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Language\Handler
     */
    protected ?MockObject $innerHandlerMock = null;

    /**
     * Language cache mock.
     *
     * @var \Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache
     */
    protected ?MockObject $languageCacheMock = null;

    /** @var \Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface */
    protected ?MockObject $cacheIdentifierGeneratorMock = null;

    public function testCreate(): void
    {
        $handler = $this->getLanguageHandler();
        $innerHandlerMock = $this->getInnerLanguageHandlerMock();
        $cacheMock = $this->getLanguageCacheMock();

        $languageFixture = $this->getLanguageFixture();

        $innerHandlerMock->expects(self::once())
            ->method('create')
            ->with(
                self::isInstanceOf(
                    SPILanguageCreateStruct::class
                )
            )->willReturn($languageFixture);

        $cacheMock->expects(self::once())
            ->method('setMulti')
            ->with(self::equalTo([$languageFixture]));

        $createStruct = $this->getCreateStructFixture();

        $result = $handler->create($createStruct);

        self::assertEquals(
            $languageFixture,
            $result
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

    /**
     * Returns a Language.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    protected function getLanguageFixture(): Language
    {
        $language = new Language();
        $language->id = 8;
        $language->languageCode = 'de-DE';

        return $language;
    }

    public function testUpdate(): void
    {
        $handler = $this->getLanguageHandler();

        $innerHandlerMock = $this->getInnerLanguageHandlerMock();
        $cacheMock = $this->getLanguageCacheMock();

        $innerHandlerMock->expects(self::once())
            ->method('update')
            ->with($this->getLanguageFixture());

        $languageFixture = $this->getLanguageFixture();
        $cacheMock->expects(self::once())
            ->method('setMulti')
            ->with(self::equalTo([$languageFixture]));

        $handler->update($languageFixture);
    }

    public function testLoad(): void
    {
        $handler = $this->getLanguageHandler();
        $cacheMock = $this->getLanguageCacheMock();
        $cacheIdentifierGeneratorMock = $this->getCacheIdentifierGeneratorMock();

        $cacheIdentifierGeneratorMock->expects(self::once())
            ->method('generateKey')
            ->with('language', [2], true)
            ->willReturn('ibx-la-2');

        $cacheMock->expects(self::once())
            ->method('get')
            ->with(self::equalTo('ibx-la-2'))
            ->willReturn($this->getLanguageFixture());

        $result = $handler->load(2);

        self::assertEquals(
            $this->getLanguageFixture(),
            $result
        );
    }

    public function testLoadFailure(): void
    {
        $handler = $this->getLanguageHandler();
        $cacheMock = $this->getLanguageCacheMock();
        $innerHandlerMock = $this->getInnerLanguageHandlerMock();
        $cacheIdentifierGeneratorMock = $this->getCacheIdentifierGeneratorMock();

        $cacheIdentifierGeneratorMock->expects(self::once())
            ->method('generateKey')
            ->with('language', [2], true)
            ->willReturn('ibx-la-2');

        $cacheMock->expects(self::once())
            ->method('get')
            ->with(self::equalTo('ibx-la-2'))
            ->willReturn(null);

        $innerHandlerMock->expects(self::once())
            ->method('load')
            ->with(self::equalTo(2))
            ->will(
                self::throwException(
                    new NotFoundException('Language', 2)
                )
            );

        $this->expectException(APINotFoundException::class);
        $handler->load(2);
    }

    public function testLoadByLanguageCode(): void
    {
        $handler = $this->getLanguageHandler();
        $cacheMock = $this->getLanguageCacheMock();
        $cacheIdentifierGeneratorMock = $this->getCacheIdentifierGeneratorMock();

        $cacheIdentifierGeneratorMock->expects(self::once())
            ->method('generateKey')
            ->with('language_code', ['eng-US'], true)
            ->willReturn('ibx-lac-eng-US');

        $cacheMock->expects(self::once())
            ->method('get')
            ->with(self::equalTo('ibx-lac-eng-US'))
            ->willReturn($this->getLanguageFixture());

        $result = $handler->loadByLanguageCode('eng-US');

        self::assertEquals(
            $this->getLanguageFixture(),
            $result
        );
    }

    public function testLoadByLanguageCodeFailure(): void
    {
        $handler = $this->getLanguageHandler();
        $cacheMock = $this->getLanguageCacheMock();
        $innerHandlerMock = $this->getInnerLanguageHandlerMock();
        $cacheIdentifierGeneratorMock = $this->getCacheIdentifierGeneratorMock();

        $cacheIdentifierGeneratorMock->expects(self::once())
            ->method('generateKey')
            ->with('language_code', ['eng-US'], true)
            ->willReturn('ibx-lac-eng-US');

        $cacheMock->expects(self::once())
            ->method('get')
            ->with(self::equalTo('ibx-lac-eng-US'))
            ->willReturn(null);

        $innerHandlerMock->expects(self::once())
            ->method('loadByLanguageCode')
            ->with(self::equalTo('eng-US'))
            ->will(
                self::throwException(
                    new NotFoundException('Language', 2)
                )
            );

        $this->expectException(APINotFoundException::class);
        $handler->loadByLanguageCode('eng-US');
    }

    public function testLoadAll(): void
    {
        $handler = $this->getLanguageHandler();
        $cacheMock = $this->getLanguageCacheMock();
        $cacheIdentifierGeneratorMock = $this->getCacheIdentifierGeneratorMock();

        $cacheIdentifierGeneratorMock->expects(self::once())
            ->method('generateKey')
            ->with('language_list', [], true)
            ->willReturn('ibx-lal');

        $cacheMock->expects(self::once())
            ->method('get')
            ->with(self::equalTo('ibx-lal'))
            ->willReturn([]);

        $result = $handler->loadAll();

        self::assertIsArray($result);
    }

    public function testDelete(): void
    {
        $handler = $this->getLanguageHandler();
        $cacheMock = $this->getLanguageCacheMock();
        $innerHandlerMock = $this->getInnerLanguageHandlerMock();
        $cacheIdentifierGeneratorMock = $this->getCacheIdentifierGeneratorMock();

        $cacheIdentifierGeneratorMock->expects(self::exactly(2))
            ->method('generateKey')
            ->withConsecutive(
                ['language', [2], true],
                ['language_list', [], true]
            )
            ->willReturnOnConsecutiveCalls(
                'ibx-la-2',
                'ibx-lal'
            );

        $innerHandlerMock->expects(self::once())
            ->method('delete')
            ->with(self::equalTo(2));

        $cacheMock->expects(self::once())
            ->method('deleteMulti')
            ->with(self::equalTo(['ibx-la-2', 'ibx-lal']));

        $handler->delete(2);
    }

    /**
     * Returns the language handler to test.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler
     */
    protected function getLanguageHandler()
    {
        if (!isset($this->languageHandler)) {
            $this->languageHandler = new CachingHandler(
                $this->getInnerLanguageHandlerMock(),
                $this->getLanguageCacheMock(),
                $this->getCacheIdentifierGeneratorMock()
            );
        }

        return $this->languageHandler;
    }

    /**
     * Returns a mock for the inner language handler.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language\Handler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getInnerLanguageHandlerMock()
    {
        if (!isset($this->innerHandlerMock)) {
            $this->innerHandlerMock = $this->createMock(SPILanguageHandler::class);
        }

        return $this->innerHandlerMock;
    }

    /**
     * Returns a mock for the in-memory cache.
     *
     * @return \Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLanguageCacheMock()
    {
        if (!isset($this->languageCacheMock)) {
            $this->languageCacheMock = $this->createMock(InMemoryCache::class);
        }

        return $this->languageCacheMock;
    }

    /**
     * @return \Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getCacheIdentifierGeneratorMock()
    {
        if (!isset($this->cacheIdentifierGeneratorMock)) {
            $this->cacheIdentifierGeneratorMock = $this->createMock(CacheIdentifierGeneratorInterface::class);
        }

        return $this->cacheIdentifierGeneratorMock;
    }

    /**
     * Returns an array with 2 languages.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language[]
     */
    protected function getLanguagesFixture(): array
    {
        $langUs = new Language();
        $langUs->id = 2;
        $langUs->languageCode = 'eng-US';
        $langUs->name = 'English (American)';
        $langUs->isEnabled = true;

        $langGb = new Language();
        $langGb->id = 4;
        $langGb->languageCode = 'eng-GB';
        $langGb->name = 'English (United Kingdom)';
        $langGb->isEnabled = true;

        return [$langUs, $langGb];
    }
}
