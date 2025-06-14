<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Language;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LanguageCreateStruct;
use UnexpectedValueException;

final class BeforeCreateLanguageEvent extends BeforeEvent
{
    private LanguageCreateStruct $languageCreateStruct;

    private ?Language $language = null;

    public function __construct(LanguageCreateStruct $languageCreateStruct)
    {
        $this->languageCreateStruct = $languageCreateStruct;
    }

    public function getLanguageCreateStruct(): LanguageCreateStruct
    {
        return $this->languageCreateStruct;
    }

    public function getLanguage(): Language
    {
        if (!$this->hasLanguage()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasLanguage() or set it using setLanguage() before you call the getter.', Language::class));
        }

        return $this->language;
    }

    public function setLanguage(?Language $language): void
    {
        $this->language = $language;
    }

    public function hasLanguage(): bool
    {
        return $this->language instanceof Language;
    }
}
