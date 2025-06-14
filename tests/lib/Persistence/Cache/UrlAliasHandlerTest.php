<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content\UrlAlias;
use Ibexa\Contracts\Core\Persistence\Content\UrlAlias\Handler as SPIUrlAliasHandler;

/**
 * Test case for Persistence\Cache\UrlAliasHandler.
 */
class UrlAliasHandlerTest extends AbstractInMemoryCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'urlAliasHandler';
    }

    public function getHandlerClassName(): string
    {
        return SPIUrlAliasHandler::class;
    }

    public function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        return [
            [
                'publishUrlAliasForLocation',
                [44, 2, 'name', 'eng-GB', true, false],
                [
                    ['url_alias_location', [44], false],
                    ['url_alias_location_path', [44], false],
                    ['url_alias', ['44-abc'], false],
                    ['url_alias_not_found', [], false],
                ],
                null,
                ['urlal-44', 'urlalp-44', 'urla-44-abc', 'urlanf'],
                null,
                '44-abc',
            ],
            [
                'createCustomUrlAlias',
                [44, '1/2/44', true, null, false],
                [
                    ['url_alias_location', [44], false],
                    ['url_alias_location_path', [44], false],
                    ['url_alias_not_found', [], false],
                    ['url_alias', [5], false],
                ],
                null,
                ['urlal-44', 'urlalp-44', 'urlanf', 'urla-5'],
                null,
                new UrlAlias(['id' => 5]),
            ],
            ['createGlobalUrlAlias', ['something', '1/2/44', true, null, false], [['url_alias_not_found', [], false]], null, ['urlanf']],
            ['createGlobalUrlAlias', ['something', '1/2/44', true, 'eng-GB', false], [['url_alias_not_found', [], false]], null, ['urlanf']],
            ['listGlobalURLAliases', ['eng-GB', 10, 50]],
            [
                'removeURLAliases',
                [[new UrlAlias(['id' => 5, 'type' => UrlAlias::LOCATION, 'isCustom' => true, 'destination' => 21])]],
                [
                    ['url_alias', [5], false],
                    ['url_alias_location', [21], false],
                    ['url_alias_location_path', [21], false],
                    ['url_alias_custom', [21], false],
                ],
                null,
                ['urla-5', 'urlal-21', 'urlalp-21', 'urlac-21'],
            ],
            [
                'locationMoved',
                [21, 45, 12],
                [
                    ['url_alias_location', [21], false],
                    ['url_alias_location_path', [21], false],
                ],
                null,
                ['urlal-21', 'urlalp-21'],
            ],
            [
                'locationCopied',
                [21, 33, 12],
                [
                    ['url_alias_location', [21], false],
                    ['url_alias_location', [33], false],
                ],
                null,
                ['urlal-21', 'urlal-33'],
            ],
            [
                'locationDeleted',
                [21],
                [
                    ['url_alias_location', [21], false],
                    ['url_alias_location_path', [21], false],
                ],
                null,
                ['urlal-21', 'urlalp-21'],
                null,
                [],
            ],
            [
                'locationSwapped',
                [21, 2, 33, 45],
                [
                    ['url_alias_location', [21], false],
                    ['url_alias_location_path', [21], false],
                    ['url_alias_location', [33], false],
                    ['url_alias_location_path', [33], false],
                ],
                null,
                ['urlal-21', 'urlalp-21', 'urlal-33', 'urlalp-33'],
            ],
            [
                'translationRemoved',
                [[21, 33], 'eng-GB'],
                [
                    ['url_alias_location', [21], false],
                    ['url_alias_location_path', [21], false],
                    ['url_alias_location', [33], false],
                    ['url_alias_location_path', [33], false],
                ],
                null,
                ['urlal-21', 'urlalp-21', 'urlal-33', 'urlalp-33'],
            ],
            [
                'archiveUrlAliasesForDeletedTranslations',
                [21, 33, ['eng-GB']],
                [
                    ['url_alias_location', [21], false],
                    ['url_alias_location_path', [21], false],
                ],
                null,
                ['urlal-21', 'urlalp-21'],
            ],
        ];
    }

    public function providerForCachedLoadMethodsHit(): array
    {
        $object = new UrlAlias(['id' => 5]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            ['listURLAliasesForLocation', [5], 'ibx-urlall-5', null, null, [['url_alias_location_list', [5], true]], ['ibx-urlall-5'], [$object]],
            ['listURLAliasesForLocation', [5, true], 'ibx-urlall-5-c', null, null, [['url_alias_location_list_custom', [5], true]], ['ibx-urlall-5-c'], [$object]],
            ['lookup', ['/Home'], 'ibx-urlau-_SHome', null, null, [['url_alias_url', ['_SHome'], true]], ['ibx-urlau-_SHome'], $object],
            ['loadUrlAlias', [5], 'ibx-urla-5', null, null, [['url_alias', [5], true]], ['ibx-urla-5'], $object],
        ];
    }

    public function providerForCachedLoadMethodsMiss(): array
    {
        $object = new UrlAlias(['id' => 5]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            [
                'listURLAliasesForLocation',
                [5],
                'ibx-urlall-5',
                [
                    ['url_alias_location', [5], false],
                    ['url_alias', [5], false],
                ],
                ['urlal-5', 'urla-5'],
                [
                    ['url_alias_location_list', [5], true],
                ],
                ['ibx-urlall-5'],
                [$object],
            ],
            [
                'listURLAliasesForLocation',
                [5, true],
                'ibx-urlall-5-c',
                [
                    ['url_alias_location', [5], false],
                    ['url_alias', [5], false],
                ],
                ['urlal-5', 'urla-5'],
                [
                    ['url_alias_location_list_custom', [5], true],
                ],
                ['ibx-urlall-5-c'],
                [$object],
            ],
            [
                'lookup',
                ['/Home'],
                'ibx-urlau-_SHome',
                [
                    ['url_alias', [5], false],
                ],
                ['urla-5'],
                [
                    ['url_alias_url', ['_SHome'], true],
                ],
                ['ibx-urlau-_SHome'],
                $object,
            ],
            [
                'loadUrlAlias',
                [5],
                'ibx-urla-5',
                [
                    ['url_alias', [5], false],
                ],
                ['urla-5'],
                [
                    ['url_alias', [5], true],
                ],
                ['ibx-urla-5'],
                $object,
            ],
        ];
    }
}
