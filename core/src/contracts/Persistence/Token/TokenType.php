<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Token;

use Ibexa\Contracts\Core\Persistence\ValueObject;

/**
 * @internal
 */
final class TokenType extends ValueObject
{
    public int $id;

    public string $identifier;
}
