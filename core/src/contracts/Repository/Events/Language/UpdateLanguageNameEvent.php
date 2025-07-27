<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Language;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;

final class UpdateLanguageNameEvent extends AfterEvent
{
    private Language $updatedLanguage;

    private Language $language;

    private string $newName;

    public function __construct(
        Language $updatedLanguage,
        Language $language,
        string $newName
    ) {
        $this->updatedLanguage = $updatedLanguage;
        $this->language = $language;
        $this->newName = $newName;
    }

    public function getUpdatedLanguage(): Language
    {
        return $this->updatedLanguage;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }
}
