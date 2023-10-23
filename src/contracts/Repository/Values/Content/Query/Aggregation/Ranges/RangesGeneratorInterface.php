<?php
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges;

interface RangesGeneratorInterface
{
    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range[]
     */
    public function generate(): iterable;
}
