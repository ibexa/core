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
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

final class CopyContentEvent extends AfterEvent
{
    private Content $content;

    private ContentInfo $contentInfo;

    private LocationCreateStruct $destinationLocationCreateStruct;

    private ?VersionInfo $versionInfo;

    public function __construct(
        Content $content,
        ContentInfo $contentInfo,
        LocationCreateStruct $destinationLocationCreateStruct,
        ?VersionInfo $versionInfo = null
    ) {
        $this->content = $content;
        $this->contentInfo = $contentInfo;
        $this->destinationLocationCreateStruct = $destinationLocationCreateStruct;
        $this->versionInfo = $versionInfo;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getDestinationLocationCreateStruct(): LocationCreateStruct
    {
        return $this->destinationLocationCreateStruct;
    }

    public function getVersionInfo(): ?VersionInfo
    {
        return $this->versionInfo;
    }
}
