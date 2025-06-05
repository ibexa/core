<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\User\Exception;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

final class PasswordHashTypeNotCompiled extends InvalidArgumentException
{
    public function __construct(string $hashType)
    {
        parent::__construct(
            'hashType',
            "Password hash algorithm $hashType is not compiled into PHP"
        );
    }
}
