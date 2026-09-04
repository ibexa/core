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
     * Returns Limitation validation errors.
     *
     * The structure depends on the method that threw the exception:
     * - assignRoleToUser(), assignRoleToUserGroup(): ValidationError[]
     * - addPolicyByRoleDraft(), updatePolicyByRoleDraft(): ValidationError[][], keyed by Limitation identifier
     * - createRole(), copyRole(): ValidationError[][][], keyed by Policy index, then Limitation identifier
     *
     * @return array<mixed>
     */
    abstract public function getLimitationErrors();
}

class_alias(LimitationValidationException::class, 'eZ\Publish\API\Repository\Exceptions\LimitationValidationException');
