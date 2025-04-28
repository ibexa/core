<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard\Handler as SpiUrlWildcardHandler;

class UrlWildcardHandlerTest extends AbstractCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'urlWildcardHandler';
    }

    public function getHandlerClassName(): string
    {
        return SpiUrlWildcardHandler::class;
    }

    public function providerForUnCachedMethods(): iterable
    {
        $wildcard = new UrlWildcard(['id' => 1]);

        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        yield 'create' => ['create', ['/home/about', '/web3/some/page/link', true], [['url_wildcard_not_found', [], false]], null, ['urlwnf'], null, $wildcard];
        yield 'remove' => ['remove', [1], [['url_wildcard', [1], false]], null, ['urlw-1']];
        yield 'loadAll' => ['loadAll', [], null, null, null, null, [$wildcard]];
        yield 'exactSourceUrlExists' => ['exactSourceUrlExists', ['/home/about'], null, null, null, null, true];
    }

    public function providerForCachedLoadMethodsHit(): array
    {
        $wildcard = new UrlWildcard(['id' => 1]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            ['load', [1], 'ibx-urlw-1', null, null, [['url_wildcard', [1], true]], ['ibx-urlw-1'], $wildcard],
            ['translate', ['/home/about'], 'ibx-urlws-_Shome_Sabout', null, null,  [['url_wildcard_source', ['_Shome_Sabout'], true]], ['ibx-urlws-_Shome_Sabout'], $wildcard],
        ];
    }

    public function providerForCachedLoadMethodsMiss(): array
    {
        $wildcard = new UrlWildcard(['id' => 1]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            [
                'load',
                [1],
                'ibx-urlw-1',
                [
                    ['url_wildcard', [1], false],
                ],
                ['urlw-1'],
                [
                    ['url_wildcard', [1], true],
                ],
                ['ibx-urlw-1'],
                $wildcard,
            ],
            [
                'translate',
                ['/home/about'],
                'ibx-urlws-_Shome_Sabout',
                [
                    ['url_wildcard', [1], false],
                ],
                ['urlw-1'],
                [
                    ['url_wildcard_source', ['_Shome_Sabout'], true],
                ],
                ['ibx-urlws-_Shome_Sabout'],
                $wildcard,
            ],
        ];
    }
}
