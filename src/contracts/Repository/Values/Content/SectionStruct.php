<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class SectionStruct extends ValueObject
{
    /**
     * If set the Unique identifier of the section is changes.
     *
     * Needs to be a unique Section->identifier string value.
     */
    public ?string $identifier = null;

    /**
     * If set the name of the section is changed.
     */
    public ?string $name = null;
}
