<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;

final class Identifiers implements CriterionInterface
{
    /** @var list<string> */
    private array $value;

    /**
     * @param list<string> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    /**
     * @return list<string>
     */
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @param list<string> $value
     */
    public function setValue(array $value): void
    {
        $this->value = $value;
    }
}
