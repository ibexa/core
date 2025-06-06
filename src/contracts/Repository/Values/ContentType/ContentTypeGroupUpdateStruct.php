<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use DateTimeInterface;

/**
 * This class is used for updating a content type group.
 */
class ContentTypeGroupUpdateStruct extends ContentTypeGroupStruct
{
    /**
     * If set this value overrides the current user as modifier.
     */
    public ?int $modifierId = null;

    /**
     * If set this value overrides the current time for modified.
     */
    public ?DateTimeInterface $modificationDate = null;
}
