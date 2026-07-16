<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Exception;

use RuntimeException;
use Throwable;

/**
 * General IO exception.
 */
class IOException extends RuntimeException
{
    public function __construct(
        string $message,
        ?Throwable $e = null
    ) {
        parent::__construct($message, 0, $e);
    }
}
