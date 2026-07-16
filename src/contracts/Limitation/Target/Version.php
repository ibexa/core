<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Limitation\Target;

use Ibexa\Contracts\Core\Limitation\Target;
use Ibexa\Contracts\Core\Persistence\ValueObject;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Version Limitation target. Indicates an intent to create new Version.
 *
 * @property-read string[] $allLanguageCodesList
 * @property-read int[] $allContentTypeIdsList
 * @property-read int $newStatus
 * @property-read string $forUpdateInitialLanguageCode
 * @property-read string[] $forUpdateLanguageCodesList
 * @property-read string[] $forPublishLanguageCodesList
 * @property-read Field[] $updatedFields
 */
final class Version extends ValueObject implements Target
{
    /**
     * List of language codes of translations. At least one must match Limitation values.
     *
     * @var string[]
     */
    protected array $allLanguageCodesList = [];

    /**
     * List of content types. At least one must match Limitation values.
     *
     * @var int[]
     */
    protected array $allContentTypeIdsList = [];

    /**
     * Language code of a translation used when updated, can be null for e.g. multiple translations changed.
     */
    protected ?string $forUpdateInitialLanguageCode = null;

    /**
     * List of language codes of translations to update. All must match Limitation values.
     *
     * @var string[]
     */
    protected array $forUpdateLanguageCodesList = [];

    /**
     * List of language codes of translations to publish. All must match Limitation values.
     *
     * @var string[]
     */
    protected array $forPublishLanguageCodesList = [];

    /**
     * One of the following: STATUS_DRAFT, STATUS_PUBLISHED, STATUS_ARCHIVED.
     *
     * @see VersionInfo::STATUS_DRAFT
     * @see VersionInfo::STATUS_PUBLISHED
     * @see VersionInfo::STATUS_ARCHIVED
     *
     * @var int|null
     */
    protected ?int $newStatus = null;

    /** @var Field[] */
    protected array $updatedFields = [];

    /**
     * List of language codes of translations to delete. All must match Limitation values.
     *
     * @var string[]
     */
    private $translationsToDelete = [];

    /**
     * @param string[] $translationsToDelete List of language codes of translations to delete
     */
    public function deleteTranslations(array $translationsToDelete): self
    {
        $this->translationsToDelete = $translationsToDelete;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTranslationsToDelete(): array
    {
        return $this->translationsToDelete;
    }
}
