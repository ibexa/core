<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;

final class IsSystem implements CriterionInterface
{
    private bool $value;

    public function __construct(bool $value = true)
    {
        $this->value = $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function setValue(bool $value): void
    {
        $this->value = $value;
    }
}
