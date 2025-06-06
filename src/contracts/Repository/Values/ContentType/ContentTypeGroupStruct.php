<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class ContentTypeGroupStruct extends ValueObject
{
    /**
     * Readable and unique string identifier of a group.
     */
    public ?string $identifier;

    public bool $isSystem = false;
}
