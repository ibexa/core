<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

return SearchResult::__set_state([
   'searchHits' => [
    0 => SearchHit::__set_state([
       'valueObject' => [
        'id' => 11,
        'title' => 'Members',
      ],
       'score' => 1.0,
       'index' => null,
       'highlight' => null,
    ]),
    1 => SearchHit::__set_state([
       'valueObject' => [
        'id' => 59,
        'title' => 'Partners',
      ],
       'score' => 1.0,
       'index' => null,
       'highlight' => null,
    ]),
  ],
   'spellcheck' => null,
   'time' => 1,
   'timedOut' => null,
   'maxScore' => 1.0,
   'totalCount' => 2,
]);
