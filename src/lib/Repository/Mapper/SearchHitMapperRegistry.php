<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Mapper;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

final class SearchHitMapperRegistry implements SearchHitMapperRegistryInterface
{
    /** @var iterable<\Ibexa\Core\Repository\Mapper\SearchHitMapperInterface> */
    private iterable $mappers;

    /**
     * @param iterable<\Ibexa\Core\Repository\Mapper\SearchHitMapperInterface> $mappers
     */
    public function __construct(iterable $mappers)
    {
        $this->mappers = $mappers;
    }

    public function hasMapper(SearchHit $hit): bool
    {
        return $this->findMappers($hit) !== null;
    }

    public function getMapper(SearchHit $hit): SearchHitMapperInterface
    {
        if (!$this->hasMapper($hit)) {
            throw new InvalidArgumentException(
                '$hit',
                sprintf(
                    'undefined %s for search hit %s',
                    SearchHitMapperInterface::class,
                    get_debug_type($hit->valueObject)
                )
            );
        }

        return $this->findMappers($hit);
    }

    private function findMappers(SearchHit $hit): ?SearchHitMapperInterface
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($hit)) {
                return $mapper;
            }
        }

        return null;
    }
}
