<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageName;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class holds version information data.
 *
 * It also contains the corresponding {@see \Ibexa\Contracts\Core\Repository\Values\Content\Content} to
 * which the version belongs to.
 *
 * @property-read \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo calls getContentInfo()
 * @property-read mixed $id the internal id of the version
 * @property-read int $versionNo @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see VersionInfo::getVersionNo()} instead.
 * @property-read \DateTime $modificationDate the last modified date of this version
 * @property-read \DateTime $creationDate the creation date of this version
 * @property-read mixed $creatorId the user id of the user which created this version
 * @property-read int $status the status of this version. One of VersionInfo::STATUS_DRAFT, VersionInfo::STATUS_PUBLISHED, VersionInfo::STATUS_ARCHIVED
 * @property-read string $initialLanguageCode the language code of the version. This value is used to flag a version as a translation to specific language
 * @property-read string[] $languageCodes a collection of all languages which exist in this version.
 */
abstract class VersionInfo extends ValueObject implements MultiLanguageName
{
    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_ARCHIVED = 3;

    /**
     * Version ID.
     */
    protected int $id;

    /**
     * Version number.
     *
     * In contrast to {@see VersionInfo::$id}, this is the version number, which only
     * increments in scope of a single Content object.
     */
    protected int $versionNo;

    /**
     * the last modified date of this version.
     */
    protected DateTimeInterface $modificationDate;

    /**
     * Creator user ID.
     *
     * Creator of the version, in the search API this is referred to as the modifier of the published content.
     */
    protected int $creatorId;

    protected DateTimeInterface $creationDate;

    /**
     * One of VersionInfo::STATUS_DRAFT, VersionInfo::STATUS_PUBLISHED, VersionInfo::STATUS_ARCHIVED.
     */
    protected int $status;

    /**
     * In 4.x this is the language code which is used for labeling a translation.
     */
    protected string $initialLanguageCode;

    /**
     * List of languages in this version.
     *
     * Reflects which languages fields exists in for this version.
     *
     * @var string[]
     */
    protected array $languageCodes = [];

    /**
     * Content of the content this version belongs to.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    abstract public function getContentInfo(): ContentInfo;

    abstract public function getCreator(): User;

    abstract public function getInitialLanguage(): Language;

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Language[]
     */
    abstract public function getLanguages(): iterable;

    /**
     * @return array<string>
     */
    public function getLanguageCodes(): array
    {
        return $this->languageCodes;
    }

    public function getVersionNo(): int
    {
        return $this->versionNo;
    }

    /**
     * Returns true if version is a draft.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Returns true if version is published.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Returns true if version is archived.
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
}
