<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a language in the repository.
 *
 * @property-read mixed $id the language id
 * @property-read string $languageCode the language code in
 * @property-read string $name human readable name of the language
 * @property-read bool $enabled indicates if the language is enabled or not.
 */
class Language extends ValueObject
{
    /**
     * Constant for use in API's to specify that you want to load all languages.
     */
    public const array ALL = [];

    /**
     * The language id (auto generated).
     */
    protected int $id;

    protected string $languageCode;

    /**
     * Human-readable name of the language.
     */
    protected string $name;

    protected bool $enabled;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
