<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Exceptions;

/**
 * This Exception is thrown on create, update or assign policy or role
 * when one or more given limitations are not valid.
 */
abstract class LimitationValidationException extends ForbiddenException
{
    /**
     * Returns an array of limitation validation error messages.
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    abstract public function getLimitationErrors(): array;
}
