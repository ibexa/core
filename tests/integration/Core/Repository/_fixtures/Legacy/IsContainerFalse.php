<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

return new SearchResult([
    'facets' => [],
    'searchHits' => [
            new SearchHit([
                'valueObject' => [
                        'id' => 10,
                        'title' => 'Anonymous User',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 14,
                        'title' => 'Administrator User',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 52,
                        'title' => 'Common INI settings',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 54,
                        'title' => 'Ibexa Demo Design (without demo content)',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
        ],
    'spellSuggestion' => null,
    'time' => 1,
    'timedOut' => null,
    'maxScore' => null,
    'totalCount' => 4,
]);
