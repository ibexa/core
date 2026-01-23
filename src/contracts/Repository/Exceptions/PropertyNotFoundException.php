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
 * This Exception is thrown if an accessed property in a value object was not found.
 */
class PropertyNotFoundException extends Exception implements RepositoryException
{
    /**
     * Generates: Property '{$propertyName}' not found.
     *
     * @param string|null $className Optionally to specify class in abstract/parent classes
     */
    public function __construct(
        string $propertyName,
        ?string $className = null,
        ?Throwable $previous = null
    ) {
        if ($className === null) {
            parent::__construct("Property '{$propertyName}' not found", 0, $previous);
        } else {
            parent::__construct("Property '{$propertyName}' not found on class '{$className}'", 0, $previous);
        }
    }
}
