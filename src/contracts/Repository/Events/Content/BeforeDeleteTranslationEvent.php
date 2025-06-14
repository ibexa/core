<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;

final class BeforeDeleteTranslationEvent extends BeforeEvent
{
    private ContentInfo $contentInfo;

    private string $languageCode;

    public function __construct(ContentInfo $contentInfo, string $languageCode)
    {
        $this->contentInfo = $contentInfo;
        $this->languageCode = $languageCode;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
}
