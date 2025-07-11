<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Language;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;

final class EnableLanguageEvent extends AfterEvent
{
    private Language $enabledLanguage;

    private Language $language;

    public function __construct(
        Language $enabledLanguage,
        Language $language
    ) {
        $this->enabledLanguage = $enabledLanguage;
        $this->language = $language;
    }

    public function getEnabledLanguage(): Language
    {
        return $this->enabledLanguage;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}
