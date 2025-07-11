<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ContentType;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroupCreateStruct;
use UnexpectedValueException;

final class BeforeCreateContentTypeGroupEvent extends BeforeEvent
{
    private ContentTypeGroupCreateStruct $contentTypeGroupCreateStruct;

    private ?ContentTypeGroup $contentTypeGroup = null;

    public function __construct(ContentTypeGroupCreateStruct $contentTypeGroupCreateStruct)
    {
        $this->contentTypeGroupCreateStruct = $contentTypeGroupCreateStruct;
    }

    public function getContentTypeGroupCreateStruct(): ContentTypeGroupCreateStruct
    {
        return $this->contentTypeGroupCreateStruct;
    }

    public function getContentTypeGroup(): ContentTypeGroup
    {
        if (!$this->hasContentTypeGroup()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasContentTypeGroup() or set it using setContentTypeGroup() before you call the getter.', ContentTypeGroup::class));
        }

        return $this->contentTypeGroup;
    }

    public function setContentTypeGroup(?ContentTypeGroup $contentTypeGroup): void
    {
        $this->contentTypeGroup = $contentTypeGroup;
    }

    public function hasContentTypeGroup(): bool
    {
        return $this->contentTypeGroup instanceof ContentTypeGroup;
    }
}
