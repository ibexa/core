<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Exceptions;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception as RepositoryException;
use Throwable;

/**
 * This Exception is thrown on a write attempt in a read only property in a value object.
 */
class PropertyReadOnlyException extends Exception implements RepositoryException
{
    /**
     * Generates: Property '{$propertyName}' is readonly[ on class '{$className}'].
     *
     * @param string|null $className Optionally to specify class in abstract/parent classes
     */
    public function __construct(string $propertyName, ?string $className = null, ?Throwable $previous = null)
    {
        if ($className === null) {
            parent::__construct("Property '{$propertyName}' is readonly", 0, $previous);
        } else {
            parent::__construct("Property '{$propertyName}' is readonly on class '{$className}'", 0, $previous);
        }
    }
}
