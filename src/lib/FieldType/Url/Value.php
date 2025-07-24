<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Url;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Url field type.
 */
class Value extends BaseValue
{
    public function __construct(
        public readonly ?string $link = null,
        public readonly ?string $text = null
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return (string)$this->link;
    }
}
