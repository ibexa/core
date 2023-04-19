<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Limitation;

/**
 * @internal
 */
final class LimitationIdentifierToLabelConverter
{
    public const MESSAGE_ID_PREFIX = 'policy.limitation.identifier.';

    public static function convert(string $identifier): string
    {
        return self::MESSAGE_ID_PREFIX . strtolower($identifier);
    }
}
