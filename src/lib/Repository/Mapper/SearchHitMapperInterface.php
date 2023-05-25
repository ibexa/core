<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Mapper;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Search\SearchContextInterface;

interface SearchHitMapperInterface
{
    /**
     * @return mixed
     */
    public function buildObjectOnSearchHit(SearchHit $hit, SearchContextInterface $context = null);

    public function supports(SearchHit $hit, SearchContextInterface $context = null): bool;
}
