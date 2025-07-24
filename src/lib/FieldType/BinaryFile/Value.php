<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\BinaryFile;

use Ibexa\Core\FieldType\BinaryBase\Value as BaseValue;

/**
 * Value for BinaryFile field type.
 */
class Value extends BaseValue
{
    /**
     * Number of times the file has been downloaded through content/download module.
     */
    public int $downloadCount = 0;
}
