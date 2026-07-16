<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Context of the password validation.
 *
 * @property-read ContentType|null $contentType
 * @property-read User|null $user
 */
class PasswordValidationContext extends ValueObject
{
    /**
     * Content type of the password owner.
     *
     * @var ContentType|null
     */
    protected $contentType;

    /**
     * Owner of the password.
     *
     * @var User|null
     */
    protected $user;
}
