<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Exception;

use Ibexa\Core\Base\Exceptions\BadStateException;

/**
 * Exception thrown if a non-empty Content\Type\Group is about to be deleted.
 */
class GroupNotEmpty extends BadStateException
{
    /**
     * Creates a new exception for $groupId.
     */
    public function __construct(int $groupId)
    {
        parent::__construct(
            '$groupId',
            sprintf('Group with ID "%d" is not empty.', $groupId)
        );
    }
}
