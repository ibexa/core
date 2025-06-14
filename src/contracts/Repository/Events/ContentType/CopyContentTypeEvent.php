<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ContentType;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\User;

final class CopyContentTypeEvent extends AfterEvent
{
    private ContentType $contentTypeCopy;

    private ContentType $contentType;

    private ?User $creator;

    public function __construct(
        ContentType $contentTypeCopy,
        ContentType $contentType,
        ?User $creator = null
    ) {
        $this->contentTypeCopy = $contentTypeCopy;
        $this->contentType = $contentType;
        $this->creator = $creator;
    }

    public function getContentTypeCopy(): ContentType
    {
        return $this->contentTypeCopy;
    }

    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }
}
