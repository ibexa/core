<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a value for creating a language.
 */
class LanguageCreateStruct extends ValueObject
{
    /**
     * The languageCode code.
     *
     * Needs to be a unique.
     */
    public ?string $languageCode = null;

    /**
     * Human readable name of the language.
     */
    public ?string $name;

    /**
     * Indicates if the language is enabled or not.
     */
    public bool $enabled = true;
}
