<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URLWildcard;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult;

final class TranslateEvent extends AfterEvent
{
    private string $url;

    private URLWildcardTranslationResult $result;

    public function __construct(
        URLWildcardTranslationResult $result,
        $url
    ) {
        $this->url = $url;
        $this->result = $result;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getResult(): URLWildcardTranslationResult
    {
        return $this->result;
    }
}
