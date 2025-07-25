<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Relation;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Relation field type.
 */
class Value extends BaseValue
{
    /**
     * Construct a new Value object and initialize it $destinationContent.
     *
     * @param int|null $destinationContentId Content id the relation is to
     */
    public function __construct(public readonly ?int $destinationContentId = null)
    {
        parent::__construct();
    }

    /**
     * Returns the related content item ID as a string.
     */
    public function __toString(): string
    {
        return (string)$this->destinationContentId;
    }
}
