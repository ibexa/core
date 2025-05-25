<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

final class TermAggregationResultEntry extends ValueObject
{
    private object|string|int|bool $key;

    private int $count;

    public function __construct(object|string|int|bool $key, int $count)
    {
        parent::__construct();

        $this->key = $key;
        $this->count = $count;
    }

    public function getKey(): object|string|int|bool
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
