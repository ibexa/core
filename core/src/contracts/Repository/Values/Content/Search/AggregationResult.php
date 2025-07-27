<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class AggregationResult extends ValueObject
{
    /**
     * The name of the aggregation.
     */
    private string $name;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
