<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\EmailAddress;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the EmailAddress field type.
 */
class Value extends BaseValue
{
    /**
     * Construct a new Value object and initialize its $email.
     */
    public function __construct(public readonly string $email = '')
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
