<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Exceptions;

use Throwable;

class InvalidVariationException extends InvalidArgumentException
{
    public function __construct(
        string $variationName,
        string $variationType,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("Invalid variation '$variationName' for $variationType", $code, $previous);
    }
}
