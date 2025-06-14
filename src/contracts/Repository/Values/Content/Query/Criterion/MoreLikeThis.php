<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;

/**
 * A more like this criterion is matched by content which contains similar terms
 * found in the given content, text or url fetch.
 */
class MoreLikeThis extends Criterion
{
    public const int CONTENT = 1;
    public const int TEXT = 2;
    public const int URL = 3;

    /**
     * The type of the parameter from which terms are extracted for finding similar objects.
     */
    protected int $type;

    /**
     * Creates a new more like this criterion.
     *
     * @param int $type the type (one of CONTENT,TEXT,URL)
     * @param mixed $value the value depending on the type
     *
     * @throws \InvalidArgumentException if the value type doesn't match the expected type
     */
    public function __construct(int $type, mixed $value)
    {
        $this->type = $type;

        parent::__construct(null, null, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::EQ, Specifications::FORMAT_SINGLE),
        ];
    }
}
