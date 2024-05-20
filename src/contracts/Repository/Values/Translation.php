<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values;

use Stringable;

/**
 * Abstract for UI translation messages, use its extensions: Translation\Message, Translation\Plural.
 *
 * @see \Ibexa\Contracts\Core\Repository\Values\Translation\Message
 * @see \Ibexa\Contracts\Core\Repository\Values\Translation\Plural
 */
abstract class Translation extends ValueObject implements Stringable
{
}
