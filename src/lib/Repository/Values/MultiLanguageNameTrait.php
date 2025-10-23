<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values;

/**
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
trait MultiLanguageNameTrait
{
    /**
     * Holds the collection of names with languageCode keys.
     *
     * @var string[]
     */
    protected array $names = [];

    public function getNames(): array
    {
        return $this->names;
    }

    public function getName(?string $languageCode = null): ?string
    {
        if (!empty($languageCode)) {
            return $this->names[$languageCode] ?? null;
        }

        foreach ($this->prioritizedLanguages as $prioritizedLanguageCode) {
            if (isset($this->names[$prioritizedLanguageCode])) {
                return $this->names[$prioritizedLanguageCode];
            }
        }

        if (isset($this->mainLanguageCode, $this->names[$this->mainLanguageCode])) {
            return $this->names[$this->mainLanguageCode];
        }

        return $this->names[array_key_first($this->names)] ?? null;
    }
}
