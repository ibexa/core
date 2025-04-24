<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as SPILocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\UpdateStruct;

/**
 * Test case for Persistence\Cache\LocationHandler.
 */
class LocationHandlerTest extends AbstractInMemoryCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'locationHandler';
    }

    public function getHandlerClassName(): string
    {
        return SPILocationHandler::class;
    }

    public function providerForUnCachedMethods(): iterable
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        yield 'copySubtree' => ['copySubtree', [12, 45]];
        yield 'move' => ['move', [12, 45], [['location_path', [12], false]], null, ['lp-12']];
        yield 'hide' => ['hide', [12], [['location_path', [12], false]], null, ['lp-12']];
        yield 'unHide' => ['unHide', [12], [['location_path', [12], false]], null, ['lp-12']];
        yield 'swap' => [
            'swap',
            [12, 45],
            [
                ['location', [12], false],
                ['location', [45], false],
            ],
            null,
            ['l-12', 'l-45'],
        ];
        yield 'update' => ['update', [new UpdateStruct(), 12], [['location', [12], false]], null, ['l-12']];
        yield 'create' => [
            'create',
            [new CreateStruct(['contentId' => 4, 'mainLocationId' => true])],
            [
                ['content', [4], false],
                ['role_assignment_group_list', [4], false],
            ],
            null,
            ['c-4', 'ragl-4'],
        ];
        yield 'create_not_main' => [
            'create',
            [new CreateStruct(['contentId' => 4, 'mainLocationId' => false])],
            [
                ['content', [4], false],
                ['role_assignment_group_list', [4], false],
            ],
            null,
            ['c-4', 'ragl-4'],
        ];
        yield 'removeSubtree' => ['removeSubtree', [12], [['location_path', [12], false]], null, ['lp-12']];
        yield 'deleteChildrenDrafts' => ['deleteChildrenDrafts', [12], [['location_path', [12], false]], null, ['lp-12']];
        yield 'setSectionForSubtree' => ['setSectionForSubtree', [12, 2], [['location_path', [12], false]], null, ['lp-12']];
        yield 'changeMainLocation' => ['changeMainLocation', [4, 12], [['content', [4], false]], null, ['c-4']];
        yield 'countLocationsByContent' => ['countLocationsByContent', [4]];
    }

    public function providerForCachedLoadMethodsHit(): iterable
    {
        $location = new Location(['id' => 12]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        yield 'load' => ['load', [12], 'ibx-l-12-1', null, null, [['location', [], true]], ['ibx-l'], $location];
        yield 'load_with_languages' => ['load', [12, ['eng-GB', 'bra-PG'], false], 'ibx-l-12-bra-PG|eng-GB|0', null, null, [['location', [], true]], ['ibx-l'], $location];
        yield 'loadList' => ['loadList', [[12]], 'ibx-l-12-1', null, null, [['location', [], true]], ['ibx-l'], [12 => $location], true];
        yield 'loadList_with_languages' => ['loadList', [[12], ['eng-GB', 'bra-PG'], false], 'ibx-l-12-bra-PG|eng-GB|0', null, null, [['location', [], true]], ['ibx-l'], [12 => $location], true];
        yield 'loadSubtreeIds' => ['loadSubtreeIds', [12], 'ibx-ls-12', null, null, [['location_subtree', [], true]], ['ibx-ls'], [33, 44]];
        yield 'loadLocationsByContent_with_root' => [
                'loadLocationsByContent',
                [4, 12],
                'ibx-cl-4-root-12',
                [
                    ['content', [4], false],
                    ['location', [12], false],
                    ['location_path', [12], false],
                ],
                ['c-4', 'l-12', 'lp-12'],
                [
                    ['content_locations', [], true],
                ],
                ['ibx-cl'],
                [$location],
        ];
        yield 'loadLocationsByContent' => [
                'loadLocationsByContent',
                [4],
                'ibx-cl-4',
                [
                    ['content', [4], false],
                ],
                ['c-4'],
                [
                    ['content_locations', [], true],
                ],
                ['ibx-cl'],
                [$location],
        ];
        yield 'loadParentLocationsForDraftContent' => [
                'loadParentLocationsForDraftContent',
                [4],
                'ibx-cl-4-pfd',
                null,
                null,
                [
                    ['content_locations', [], true],
                    ['parent_for_draft_suffix', [], false],
                ],
                ['ibx-cl', '-pfd'],
                [$location],
        ];
        yield 'loadByRemoteId' => ['loadByRemoteId', ['34fe5y4'], 'ibx-lri-34fe5y4-1', null, null, [['location_remote_id', [], true]], ['ibx-lri'], $location];
        yield 'loadByRemoteId_with_languages' => ['loadByRemoteId', ['34fe5y4', ['eng-GB', 'arg-ES']], 'ibx-lri-34fe5y4-arg-ES|eng-GB|1', null, null, [['location_remote_id', [], true]], ['ibx-lri'], $location];
    }

    public function providerForCachedLoadMethodsMiss(): iterable
    {
        $location = new Location(
            [
                'id' => 12,
                'contentId' => 15,
                'pathString' => '/1/2',
            ]
        );

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        yield 'load' => [
                'load',
                [12],
                'ibx-l-12-1',
                [
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-15', 'l-12', 'lp-2'],
                [
                    ['location', [], true],
                ],
                ['ibx-l'],
                $location,
        ];

        yield 'load_with_languages' => [
                'load',
                [12, ['eng-GB', 'bra-PG'], false],
                'ibx-l-12-bra-PG|eng-GB|0',
                [
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-15', 'l-12', 'lp-2'],
                [
                    ['location', [], true],
                ],
                ['ibx-l'],
                $location,
        ];

        yield 'loadList' => [
                'loadList',
                [[12]],
                'ibx-l-12-1',
                [
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-15', 'l-12', 'lp-2'],
                [
                    ['location', [], true],
                ],
                ['ibx-l'],
                [12 => $location],
                true,
        ];

        yield 'loadList_with_languages' => [
                'loadList',
                [[12], ['eng-GB', 'bra-PG'], false],
                'ibx-l-12-bra-PG|eng-GB|0',
                [
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-15', 'l-12', 'lp-2'],
                [
                    ['location', [], true],
                ],
                ['ibx-l'],
                [12 => $location],
                true,
        ];

        yield 'loadSubtreeIds' => [
                'loadSubtreeIds',
                [12],
                'ibx-ls-12',
                [
                    ['location', [12], false],
                    ['location_path', [12], false],
                    ['location', [33], false],
                    ['location_path', [33], false],
                    ['location', [44], false],
                    ['location_path', [44], false],
                ],
                ['l-12', 'lp-12', 'l-33', 'lp-33', 'l-44', 'lp-44'],
                [
                    ['location_subtree', [], true],
                ],
                ['ibx-ls'],
                [33, 44],
        ];

        yield 'loadLocationsByContent_with_root' => [
                'loadLocationsByContent',
                [4, 12],
                'ibx-cl-4-root-12',
                [
                    ['content', [4], false],
                    ['location', [12], false],
                    ['location_path', [12], false],
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-4', 'l-12', 'lp-12', 'c-15', 'l-12', 'lp-2'],
                [
                    ['content_locations', [], true],
                ],
                ['ibx-cl'],
                [$location],
        ];

        yield 'loadLocationsByContent' => [
                'loadLocationsByContent',
                [4],
                'ibx-cl-4',
                [
                    ['content', [4], false],
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-4', 'c-15', 'l-12', 'lp-2'],
                [
                    ['content_locations', [], true],
                ],
                ['ibx-cl'],
                [$location],
        ];

        yield 'loadParentLocationsForDraftContent' => [
                'loadParentLocationsForDraftContent',
                [4],
                'ibx-cl-4-pfd',
                [
                    ['content', [4], false],
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-4', 'c-15', 'l-12', 'lp-2'],
                [
                    ['content_locations', [], true],
                    ['parent_for_draft_suffix', [], false],
                ],
                ['ibx-cl', '-pfd'],
                [$location],
        ];

        yield 'loadByRemoteId' => [
                'loadByRemoteId',
                ['34fe5y4'],
                'ibx-lri-34fe5y4-1',
                [
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-15', 'l-12', 'lp-2'],
                [
                    ['location_remote_id', [], true],
                ],
                ['ibx-lri'],
                $location,
        ];

        yield 'loadByRemoteId_with_languages' => [
                'loadByRemoteId',
                ['34fe5y4', ['eng-GB', 'arg-ES']],
                'ibx-lri-34fe5y4-arg-ES|eng-GB|1',
                [
                    ['content', [15], false],
                    ['location', [12], false],
                    ['location_path', ['2'], false],
                ],
                ['c-15', 'l-12', 'lp-2'],
                [
                    ['location_remote_id', [], true],
                ],
                ['ibx-lri'],
                $location,
        ];
    }
}
