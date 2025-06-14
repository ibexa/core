<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageDescription;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageName;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a content type group value.
 *
 * @property-read mixed $id the id of the content type group
 * @property-read string $identifier the identifier of the content type group
 * @property-read \DateTime $creationDate the date of the creation of this content type group
 * @property-read \DateTime $modificationDate the date of the last modification of this content type group
 * @property-read mixed $creatorId the user id of the creator of this content type group
 * @property-read mixed $modifierId the user id of the user which has last modified this content type group
 */
abstract class ContentTypeGroup extends ValueObject implements MultiLanguageName, MultiLanguageDescription
{
    /**
     * Primary key.
     */
    protected int $id;

    /**
     * Readable string identifier of a group.
     */
    protected string $identifier;

    /**
     * Created date (timestamp).
     */
    protected DateTimeInterface $creationDate;

    /**
     * Modified date (timestamp).
     */
    protected DateTimeInterface $modificationDate;

    /**
     * Creator user id.
     */
    protected int $creatorId;

    /**
     * Modifier user id.
     */
    protected int $modifierId;

    public bool $isSystem = false;
}
