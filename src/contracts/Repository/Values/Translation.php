<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values;

/**
 * Abstract for UI translation messages, use its extensions: Translation\Message, Translation\Plural.
 *
 * @see \Ibexa\Contracts\Core\Repository\Values\Translation\Message
 * @see \Ibexa\Contracts\Core\Repository\Values\Translation\Plural
 */
abstract class Translation extends ValueObject
{
    public function __toString(): string
    {
        trigger_deprecation(
            'ibexa/core',
            '4.6',
            sprintf(
                'Not overriding `%s(): string` method in `%s` is deprecated, will cause fatal error in 5.0.',
                __METHOD__,
                static::class,
            )
        );

        return '';
    }
}

class_alias(Translation::class, 'eZ\Publish\API\Repository\Values\Translation');
