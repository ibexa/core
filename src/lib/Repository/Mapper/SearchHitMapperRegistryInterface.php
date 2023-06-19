<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Mapper;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;

interface SearchHitMapperRegistryInterface
{
    public function hasMapper(SearchHit $hit): bool;

    public function getMapper(SearchHit $hit): SearchHitMapperInterface;
}
