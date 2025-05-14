<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class Embedding extends ValueObject
{
    /** @var float[] */
    protected array $value;

    /**
     * @param float[] $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    /** @return float[] */
    public function getValue(): array
    {
        return $this->value;
    }
}
