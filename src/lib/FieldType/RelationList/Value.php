<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\RelationList;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the RelationList field type.
 */
class Value extends BaseValue
{
    /**
     * @param int[] $destinationContentIds
     */
    public function __construct(public readonly array $destinationContentIds = [])
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return implode(',', $this->destinationContentIds);
    }
}
