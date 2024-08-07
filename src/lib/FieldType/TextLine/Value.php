<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\TextLine;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for TextLine field type.
 */
class Value extends BaseValue
{
    /**
     * Text content.
     */
    public string $text;

    public function __construct(?string $text = '')
    {
        parent::__construct();

        $this->text = (string)$text;
    }

    public function __toString()
    {
        return $this->text;
    }
}
