<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Exception;

use Ibexa\Core\Base\Exceptions\BadStateException;

/**
 * Exception thrown when a Type is to be unlinked from its last Group.
 */
class TypeStillHasContent extends BadStateException
{
    /**
     * Creates a new exception for `$typeId` of `$status`.
     */
    public function __construct(
        int $typeId,
        int $status
    ) {
        parent::__construct(
            '$typeId',
            sprintf(
                'Type with ID "%d" in status "%d" still has content and cannot be deleted.',
                $typeId,
                $status
            )
        );
    }
}
