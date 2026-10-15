<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\DataSetBuilder;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\TermAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\TermAggregationResultEntry;

/**
 * @internal
 */
final class TermAggregationDataSetBuilder
{
    /** @var Aggregation */
    private $aggregation;

    /** @var array */
    private $entries;

    /** @var callable|null */
    private $mapper;

    public function __construct(Aggregation $aggregation)
    {
        $this->aggregation = $aggregation;
        $this->entries = [];
        $this->mapper = null;
    }

    public function setExpectedEntries(array $entries): self
    {
        $this->entries = $entries;

        return $this;
    }

    public function setEntryMapper(callable $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    public function build(): array
    {
        return [
            $this->aggregation,
            $this->buildExpectedTermAggregationResult(),
        ];
    }

    /**
     * @phpstan-return TermAggregationResult<object|scalar>
     */
    private function buildExpectedTermAggregationResult(): TermAggregationResult
    {
        $entries = [];
        foreach ($this->entries as $key => $count) {
            if ($this->mapper !== null) {
                $key = ($this->mapper)($key);
            }

            $entries[] = new TermAggregationResultEntry($key, $count);
        }

        return TermAggregationResult::createForAggregation($this->aggregation, $entries);
    }
}
