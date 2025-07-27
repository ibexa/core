<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * With this class data can be provided to update version independent fields of the content.
 * It is used in content update methods. At least one property in this class must be set.
 */
class ContentMetadataUpdateStruct extends ValueObject
{
    /**
     * If set this value changes the owner id of the content object.
     */
    public ?int $ownerId = null;

    /**
     * If set this value overrides the publication date of the content. (Used in staging scenarios).
     */
    public ?DateTimeInterface $publishedDate = null;

    /**
     * If set this value overrides the modification date. (Used for staging scenarios).
     */
    public ?DateTimeInterface $modificationDate = null;

    /**
     * If set the main language of the content object is changed.
     */
    public ?string $mainLanguageCode = null;

    /**
     * If set this value changes the always available flag.
     */
    public ?bool $alwaysAvailable = null;

    /**
     * If set this value changes the remoteId.
     *
     * Needs to be a unique Content->remoteId string value.
     */
    public ?string $remoteId = null;

    /**
     * If set  main location is changed to this value.
     *
     * If the content object has multiple locations,
     * $mainLocationId will point to the main one.
     */
    public ?int $mainLocationId = null;

    /**
     * If set, will change the content's "always-available" name.
     */
    public ?string $name = null;
}
