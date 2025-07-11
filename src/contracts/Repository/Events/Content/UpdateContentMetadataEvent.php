<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;

final class UpdateContentMetadataEvent extends AfterEvent
{
    private Content $content;

    private ContentInfo $contentInfo;

    private ContentMetadataUpdateStruct $contentMetadataUpdateStruct;

    public function __construct(
        Content $content,
        ContentInfo $contentInfo,
        ContentMetadataUpdateStruct $contentMetadataUpdateStruct
    ) {
        $this->content = $content;
        $this->contentInfo = $contentInfo;
        $this->contentMetadataUpdateStruct = $contentMetadataUpdateStruct;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getContentMetadataUpdateStruct(): ContentMetadataUpdateStruct
    {
        return $this->contentMetadataUpdateStruct;
    }
}
