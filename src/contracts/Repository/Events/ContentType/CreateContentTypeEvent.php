<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ContentType;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;

final class CreateContentTypeEvent extends AfterEvent
{
    private ContentTypeDraft $contentTypeDraft;

    private ContentTypeCreateStruct $contentTypeCreateStruct;

    /** @var ContentTypeGroup[] */
    private array $contentTypeGroups;

    /**
     * @param ContentTypeGroup[] $contentTypeGroups
     */
    public function __construct(
        ContentTypeDraft $contentTypeDraft,
        ContentTypeCreateStruct $contentTypeCreateStruct,
        array $contentTypeGroups
    ) {
        $this->contentTypeDraft = $contentTypeDraft;
        $this->contentTypeCreateStruct = $contentTypeCreateStruct;
        $this->contentTypeGroups = $contentTypeGroups;
    }

    public function getContentTypeDraft(): ContentTypeDraft
    {
        return $this->contentTypeDraft;
    }

    public function getContentTypeCreateStruct(): ContentTypeCreateStruct
    {
        return $this->contentTypeCreateStruct;
    }

    /**
     * @return ContentTypeGroup[]
     */
    public function getContentTypeGroups(): array
    {
        return $this->contentTypeGroups;
    }
}
