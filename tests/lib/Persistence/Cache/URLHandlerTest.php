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

    public static function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        return [
            ['find', [new URLQuery()]],
            ['findUsages', [1]],
            ['loadByUrl', ['http://google.com']],
        ];
    }

    public static function providerForCachedLoadMethodsHit(): array
    {
        $url = new URL(['id' => 1]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            ['loadById', [1], 'ibx-url-1', null, null, [['url', [1], true]], ['ibx-url-1'], [$url]],
        ];
    }

    public static function providerForCachedLoadMethodsMiss(): array
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
        $matcher = self::exactly(4);

        $this->cacheIdentifierGeneratorMock
            ->expects($matcher)
            ->method('generateTag')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('url', $parameters[0]);
                    $this->assertSame([1], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'url-1';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('content', $parameters[0]);
                    $this->assertSame([2], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'c-2';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame('content', $parameters[0]);
                    $this->assertSame([3], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'c-3';
                }
                if ($matcher->numberOfInvocations() === 4) {
                    $this->assertSame('content', $parameters[0]);
                    $this->assertSame([5], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'c-5';
                }
            });

        $invalidateTagsMatcher = self::exactly(2);
        $this->cacheMock
            ->expects($invalidateTagsMatcher)
            ->method('invalidateTags')
            ->willReturnCallback(static function (array $tags) use ($invalidateTagsMatcher): bool {
                if ($invalidateTagsMatcher->numberOfInvocations() === 1) {
                    self::assertSame(['url-1'], $tags);
                } else {
                    self::assertSame(['c-2', 'c-3', 'c-5'], $tags);
                }

                return true;
            });

        $handler = $this->persistenceCacheHandler->urlHandler();
        $handler->updateUrl($urlId, $updateStruct);
    }

    public function testUpdateUrlStatusIsUpdated(): void
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
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['url-1']);

        $handler = $this->persistenceCacheHandler->urlHandler();
        $handler->updateUrl($urlId, $updateStruct);
    }
}
