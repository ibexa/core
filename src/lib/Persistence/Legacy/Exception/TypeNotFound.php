<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Exception;

use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * Exception thrown when a Type to be loaded is not found.
 */
class TypeNotFound extends NotFoundException
{
    /**
     * Creates a new exception for $typeIdentifier of `$status`.
     *
     * @param string $typeIdentifier can be either a string representation of a numeric ID or a string identifier.
     * @param int $status
     */
    public function __construct(
        string $typeIdentifier,
        int $status
    ) {
        parent::__construct(
            'Persistence content type',
            sprintf('ID: %s, Status: %d', $typeIdentifier, $status)
        );
    }
}
