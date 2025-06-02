<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\URL\Handler as SpiURLHandler;
use Ibexa\Contracts\Core\Persistence\URL\URL;
use Ibexa\Contracts\Core\Persistence\URL\URLUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\URL\URLQuery;

class URLHandlerTest extends AbstractCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'urlHandler';
    }

    public function getHandlerClassName(): string
    {
        return SpiURLHandler::class;
    }

    public function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        return [
            ['find', [new URLQuery()]],
            ['findUsages', [1]],
            ['loadByUrl', ['http://google.com']],
        ];
    }

    public function providerForCachedLoadMethodsHit(): array
    {
        $url = new URL(['id' => 1]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            ['loadById', [1], 'ibx-url-1', null, null, [['url', [1], true]], ['ibx-url-1'], [$url]],
        ];
    }

    public function providerForCachedLoadMethodsMiss(): array
    {
        $url = new URL(['id' => 1]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            [
                'loadById',
                [1],
                'ibx-url-1',
                [
                    ['url', [1], false],
                ],
                ['url-1'],
                [
                    ['url', [1], true],
                ],
                ['ibx-url-1'],
                [$url],
            ],
        ];
    }

    public function testUpdateUrlWhenAddressIsUpdated(): void
    {
        $urlId = 1;
        $updateStruct = new URLUpdateStruct();
        $updateStruct->url = 'http://ibexa.co';

        $this->loggerMock->expects(self::once())->method('logCall');

        $innerHandlerMock = $this->createMock(SpiURLHandler::class);
        $this->persistenceHandlerMock
            ->method('urlHandler')
            ->willReturn($innerHandlerMock);

        $innerHandlerMock
            ->expects(self::once())
            ->method('findUsages')
            ->with($urlId)
            ->willReturn([2, 3, 5]);

        $innerHandlerMock
            ->expects(self::once())
            ->method('updateUrl')
            ->with($urlId, $updateStruct)
            ->willReturn(true);

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(4))
            ->method('generateTag')
            ->withConsecutive(
                ['url', [1], false],
                ['content', [2], false],
                ['content', [3], false],
                ['content', [5], false]
            )
            ->willReturnOnConsecutiveCalls(
                'url-1',
                'c-2',
                'c-3',
                'c-5'
            );

        $this->cacheMock
            ->expects(self::at(0))
            ->method('invalidateTags')
            ->with(['url-1']);

        $this->cacheMock
            ->expects(self::at(1))
            ->method('invalidateTags')
            ->with(['c-2', 'c-3', 'c-5']);

        $handler = $this->persistenceCacheHandler->urlHandler();
        $handler->updateUrl($urlId, $updateStruct);
    }

    public function testUpdateUrlStatusIsUpdated()
    {
        $urlId = 1;
        $updateStruct = new URLUpdateStruct();

        $this->loggerMock->expects(self::once())->method('logCall');

        $innerHandlerMock = $this->createMock(SpiURLHandler::class);
        $this->persistenceHandlerMock
            ->method('urlHandler')
            ->willReturn($innerHandlerMock);

        $innerHandlerMock
            ->expects(self::once())
            ->method('updateUrl')
            ->with($urlId, $updateStruct)
            ->willReturn(true);

        $this->cacheIdentifierGeneratorMock
            ->expects(self::once())
            ->method('generateTag')
            ->with('url', [1], false)
            ->willReturn('url-1');

        $this->cacheMock
            ->expects(self::at(0))
            ->method('invalidateTags')
            ->with(['url-1']);

        $handler = $this->persistenceCacheHandler->urlHandler();
        $handler->updateUrl($urlId, $updateStruct);
    }
}
