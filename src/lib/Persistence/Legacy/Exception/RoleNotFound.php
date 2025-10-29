<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Exception;

use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * Exception thrown when a Role/RoleDraft to be loaded is not found.
 */
class RoleNotFound extends NotFoundException
{
    /**
     * Creates a new exception for `$roleIdentifier` of `$status`.
     */
    public function __construct(
        string $roleIdentifier,
        int $status
    ) {
        parent::__construct(
            'Persistence User Role',
            sprintf('ID: %s, Status: %d', $roleIdentifier, $status)
        );
    }
}
