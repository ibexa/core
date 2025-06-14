<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ContentType;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft;

final class RemoveContentTypeTranslationEvent extends AfterEvent
{
    private ContentTypeDraft $newContentTypeDraft;

    private ContentTypeDraft $contentTypeDraft;

    private string $languageCode;

    public function __construct(
        ContentTypeDraft $newContentTypeDraft,
        ContentTypeDraft $contentTypeDraft,
        string $languageCode
    ) {
        $this->newContentTypeDraft = $newContentTypeDraft;
        $this->contentTypeDraft = $contentTypeDraft;
        $this->languageCode = $languageCode;
    }

    public function getNewContentTypeDraft(): ContentTypeDraft
    {
        return $this->newContentTypeDraft;
    }

    public function getContentTypeDraft(): ContentTypeDraft
    {
        return $this->contentTypeDraft;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
}
