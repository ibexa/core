<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * this class represents a content object in a specific version.
 *
 * @property-read ContentInfo $contentInfo @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see Content::getContentInfo()} instead.
 * @property-read int $id @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see Content::getId()} instead.
 * @property-read VersionInfo $versionInfo calls getVersionInfo()
 * @property-read array<string, array<string, \Ibexa\Core\FieldType\Value>> $fields an array of <code>[field definition identifier => [language code => field value]]</code>
 * @property-read Thumbnail|null $thumbnail calls getThumbnail()
 */
abstract class Content extends ValueObject
{
    public function getId(): int
    {
        return $this->getContentInfo()->getId();
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->getVersionInfo()->getContentInfo();
    }

    /**
     * Returns the VersionInfo for this version.
     *
     * @return VersionInfo
     */
    abstract public function getVersionInfo(): VersionInfo;

    /**
     * Shorthand method for getVersionInfo()->getName().
     *
     * @see \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo::getName()
     *
     * @param string|null $languageCode
     *
     * @return string|null The name for a given language, or null if $languageCode is not set
     *         or does not exist.
     */
    public function getName(?string $languageCode = null): ?string
    {
        return $this->getVersionInfo()->getName($languageCode);
    }

    /**
     * Returns a field value for the given value.
     *
     * - If $languageCode is defined,
     *      return if available, otherwise null
     * - If not pick using the following languages codes when applicable:
     *      1. Prioritized languages (if provided to api on object retrieval)
     *      2. Main language
     *
     * On non translatable fields this method ignores the languageCode parameter, and return main language field value.
     *
     * @param string $fieldDefIdentifier
     * @param string|null $languageCode
     *
     * @return Value|null a primitive type or a field type Value object depending on the field type.
     */
    abstract public function getFieldValue(
        string $fieldDefIdentifier,
        ?string $languageCode = null
    ): ?Value;

    /**
     * This method returns the complete fields collection.
     *
     * @return Field[] An array of {@see Field}
     */
    abstract public function getFields(): iterable;

    /**
     * This method returns the fields for a given language and non translatable fields.
     *
     * - If $languageCode is defined, return if available
     * - If not pick using prioritized languages (if provided to api on object retrieval)
     * - Otherwise return in main language
     *
     * @param string|null $languageCode
     *
     * @return iterable<string, Field>  An array of {@see Field} with field identifier as keys
     */
    abstract public function getFieldsByLanguage(?string $languageCode = null): iterable;

    /**
     * This method returns the field for a given field definition identifier and language.
     *
     * - If $languageCode is defined,
     *      return if available, otherwise null
     * - If not pick using the following languages codes when applicable:
     *      1. Prioritized languages (if provided to api on object retrieval)
     *      2. Main language
     *
     * On non translatable fields this method ignores the languageCode parameter, and return main language field.
     *
     * @param string $fieldDefIdentifier
     * @param string|null $languageCode
     *
     * @return Field|null A {@see Field} or null if nothing is found
     */
    abstract public function getField(
        string $fieldDefIdentifier,
        ?string $languageCode = null
    ): ?Field;

    /**
     * Returns the ContentType for this content.
     *
     * @return ContentType
     */
    abstract public function getContentType(): ContentType;

    abstract public function getThumbnail(): ?Thumbnail;

    abstract public function getDefaultLanguageCode(): string;
}
