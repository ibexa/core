<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;

final class ContentTypeGroupId implements CriterionInterface
{
    /** @var list<int>|int */
    private $value;

    /**
     * @param list<int>|int $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return list<int>|int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param list<int>|int $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
