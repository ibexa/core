<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use DateTimeInterface;

/**
 * This class is used for creating a content type group.
 */
class ContentTypeGroupCreateStruct extends ContentTypeGroupStruct
{
    /**
     * If set this value overrides the current user as creator.
     */
    public ?int $creatorId = null;

    /**
     * If set this value overrides the current time for creation.
     */
    public ?DateTimeInterface $creationDate = null;
}
