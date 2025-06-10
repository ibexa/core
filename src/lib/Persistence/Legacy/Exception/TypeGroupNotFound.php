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
class TypeGroupNotFound extends NotFoundException
{
    /**
     * @param string $typeGroupIdentifier can be either a string representation of a numeric ID or a string identifier.
     */
    public function __construct(string $typeGroupIdentifier)
    {
        parent::__construct(
            'Persistence content type group',
            sprintf('ID: %s', $typeGroupIdentifier)
        );
    }
}
