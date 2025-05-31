<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class provides all version independent information of the Content object.
 *
 * @property-read int $id @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see ContentInfo::getId()} instead.
 * @property-read int $contentTypeId The unique id of the content type item the Content object is an instance of
 * @property-read string $name @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see ContentInfo::getName()} instead.
 * @property-read int $sectionId @deprecated 4.6.2 Use {@see ContentInfo::getSectionId} instead. The section to which the Content object is assigned
 * @property-read int $currentVersionNo Current Version number is the version number of the published version or the version number of a newly created draft (which is 1).
 * @property-read bool $published true if there exists a published version false otherwise
 * @property-read int $ownerId the user id of the owner of the Content object
 * @property-read \DateTime $modificationDate Content object modification date
 * @property-read \DateTime $publishedDate date of the first publish
 * @property-read bool $alwaysAvailable Indicates if the Content object is shown in the mainlanguage if its not present in an other requested language
 * @property-read string $remoteId a global unique id of the Content object
 * @property-read string $mainLanguageCode The main language code of the Content object. If the available flag is set to true the Content is shown in this language if the requested language does not exist.
 * @property-read int|null $mainLocationId @deprecated Use {@see ContentInfo::getMainLocationId} instead
 * @property-read int $status status of the Content object
 * @property-read bool $isHidden @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see ContentInfo::isHidden()} instead.
 */
class ContentInfo extends ValueObject
{
    public const int STATUS_DRAFT = 0;
    public const int STATUS_PUBLISHED = 1;
    public const int STATUS_TRASHED = 2;

    /**
     * The unique id of the Content object.
     */
    protected int $id;

    /**
     * The content type id of the Content object.
     */
    protected int $contentTypeId;

    /**
     * The computed name (via name schema) in the main language of the Content object.
     *
     * For names in other languages then main see {@see \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo}
     */
    protected string $name;

    /**
     * The section to which the Content object is assigned.
     */
    protected int $sectionId;

    /**
     * Current Version number is the version number of the published version or the version number of
     * a newly created draft (which is 1).
     */
    protected int $currentVersionNo;

    /**
     * True if there exists a published version, false otherwise.
     */
    protected bool $published;

    /**
     * The owner of the Content object.
     */
    protected int $ownerId;

    /**
     * Content modification date.
     */
    protected DateTimeInterface $modificationDate;

    /**
     * Content publication date.
     */
    protected DateTimeInterface $publishedDate;

    /**
     * Indicates if the Content object is shown in the mainlanguage if its not present in an other requested language.
     */
    protected bool $alwaysAvailable;

    /**
     * Remote identifier used as a custom identifier for the object.
     */
    protected string $remoteId;

    /**
     * The main language code of the Content object.
     */
    protected string $mainLanguageCode;

    /**
     * Identifier of the main location.
     *
     * If the Content object has multiple locations,
     * $mainLocationId will point to the main one.
     */
    protected ?int $mainLocationId;

    /**
     * Status of the content.
     *
     * Replaces deprecated API\ContentInfo::$published.
     */
    protected int $status;

    protected bool $isHidden;

    protected ContentType $contentType;

    protected Section $section;

    protected Language $mainLanguage;

    protected ?Location $mainLocation;

    protected User $owner;

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isTrashed(): bool
    {
        return $this->status === self::STATUS_TRASHED;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    public function getSection(): Section
    {
        return $this->section;
    }

    public function getSectionId(): int
    {
        return $this->sectionId;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getMainLanguageCode(): string
    {
        return $this->mainLanguageCode;
    }

    public function getMainLocation(): ?Location
    {
        return $this->mainLocation;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getMainLocationId(): ?int
    {
        return $this->mainLocationId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
