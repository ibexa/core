<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

return new SearchResult([
    'searchHits' => [
            new SearchHit([
                'valueObject' => [
                        'id' => 4,
                        'title' => 'Users',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 11,
                        'title' => 'Members',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 12,
                        'title' => 'Administrator users',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 13,
                        'title' => 'Editors',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
            new SearchHit([
                'valueObject' => [
                        'id' => 41,
                        'title' => 'Media',
                    ],
                'score' => null,
                'index' => null,
                'highlight' => null,
                'matchedTranslation' => 'eng-US',
            ]),
        ],
    'spellcheck' => null,
    'time' => 1,
    'timedOut' => null,
    'maxScore' => null,
    'totalCount' => 15,
]);
