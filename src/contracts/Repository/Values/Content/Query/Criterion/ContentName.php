<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;

final class ContentName extends Criterion
{
    public function __construct(string $value)
    {
        parent::__construct(null, Operator::LIKE, $value);
    }

    /**
     * @return array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications>
     */
    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::LIKE, Specifications::FORMAT_SINGLE),
        ];
    }
}
