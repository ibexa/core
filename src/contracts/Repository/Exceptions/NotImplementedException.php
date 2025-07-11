<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Exceptions;

use Throwable;

/**
 * This Exception is thrown if a feature has not been implemented
 * _intentionally_. The main purpose is the search handler, where some features
 * are just not supported in the legacy search implementation.
 */
class NotImplementedException extends ForbiddenException
{
    /**
     * Generates: Intentionally not implemented: {$feature}.
     */
    public function __construct(string $feature, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Intentionally not implemented: {$feature}", $code, $previous);
    }
}
