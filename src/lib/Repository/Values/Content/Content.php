<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Thumbnail;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

/**
 * this class represents a content object in a specific version.
 *
 * @property-read ContentInfo $contentInfo convenience getter for $versionInfo->contentInfo
 * @property-read ContentType $contentType convenience getter for $versionInfo->contentInfo->contentType
 * @property-read int $id convenience getter for retrieving the contentId: $versionInfo->content->id
 * @property-read VersionInfo $versionInfo calls getVersionInfo()
 * @property-read array<string, array<string, \Ibexa\Core\FieldType\Value>> $fields an array of <code>[field definition identifier => [language code => field value]]</code>
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class Content extends APIContent
{
    /** @var Thumbnail|null */
    protected $thumbnail;

    /** @var array<string, array<string, \Ibexa\Core\FieldType\Value>> An array of field values like <code>[field definition identifier => [language code => field value]] => */
    protected array $fields = [];

    /** @var VersionInfo */
    protected $versionInfo;

    /** @var ContentType */
    protected $contentType;

    /** @var Field[] An array of {@link Field} */
    private $internalFields;

    /**
     * In-memory cache of Field Definition Identifier and Language Code mapped to a Field instance.
     *
     * <code>$fieldDefinitionTranslationMap[$fieldDefIdentifier][$languageCode] = $field</code>
     *
     * @var array<string, array<string, \eZ\Publish\API\Repository\Values\Content\Field>>
     */
    private $fieldDefinitionTranslationMap = [];

    /**
     * The first matched field language among user provided prioritized languages.
     *
     * The first matched language among user provided prioritized languages on object retrieval, or null if none
     * provided (all languages) or on main fallback.
     *
     * @internal
     *
     * @var string|null
     */
    protected $prioritizedFieldLanguageCode;

    public function __construct(array $data = [])
    {
        parent::__construct([]);

        $this->thumbnail = $data['thumbnail'] ?? null;
        $this->versionInfo = $data['versionInfo'] ?? null;
        $this->contentType = $data['contentType'] ?? null;
        $this->internalFields = $data['internalFields'] ?? [];
        $this->prioritizedFieldLanguageCode = $data['prioritizedFieldLanguageCode'] ?? null;

        foreach ($this->internalFields as $field) {
            $languageCode = $field->getLanguageCode();
            $fieldDefinitionIdentifier = $field->getFieldDefinitionIdentifier();
            $this->fieldDefinitionTranslationMap[$fieldDefinitionIdentifier][$languageCode] = $field;
            // kept for BC due to property-read magic getter
            $this->fields[$fieldDefinitionIdentifier][$languageCode] = $field->getValue();
        }
    }

    public function getThumbnail(): ?Thumbnail
    {
        return $this->thumbnail;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionInfo(): APIVersionInfo
    {
        return $this->versionInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue(
        string $fieldDefIdentifier,
        ?string $languageCode = null
    ): ?Value {
        if (null === $languageCode) {
            $languageCode = $this->getDefaultLanguageCode();
        }

        if (!isset($this->fieldDefinitionTranslationMap[$fieldDefIdentifier][$languageCode])) {
            return null;
        }

        return $this->fieldDefinitionTranslationMap[$fieldDefIdentifier][$languageCode]->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(): iterable
    {
        return $this->internalFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsByLanguage(?string $languageCode = null): iterable
    {
        $fields = [];

        if (null === $languageCode) {
            $languageCode = $this->getDefaultLanguageCode();
        }

        $filteredFields = array_filter(
            $this->internalFields,
            static function (Field $field) use ($languageCode): bool {
                return $field->languageCode === $languageCode;
            }
        );
        foreach ($filteredFields as $field) {
            $fields[$field->fieldDefIdentifier] = $field;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getField(
        string $fieldDefIdentifier,
        ?string $languageCode = null
    ): ?Field {
        if (null === $languageCode) {
            $languageCode = $this->getDefaultLanguageCode();
        }

        return $this->fieldDefinitionTranslationMap[$fieldDefIdentifier][$languageCode] ?? null;
    }

    public function getDefaultLanguageCode(): string
    {
        return $this->prioritizedFieldLanguageCode ?? $this->versionInfo->contentInfo->mainLanguageCode;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProperties(
        $dynamicProperties = ['id',
            'contentInfo']
    ) {
        return parent::getProperties($dynamicProperties);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($property)
    {
        switch ($property) {
            case 'id':
                return $this->getVersionInfo()->getContentInfo()->getId();

            case 'contentInfo':
                return $this->getVersionInfo()->getContentInfo();

            case 'thumbnail':
                return $this->getThumbnail();
        }

        return parent::__get($property);
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($property)
    {
        if ($property === 'id') {
            return true;
        }

        if ($property === 'contentInfo') {
            return true;
        }

        return parent::__isset($property);
    }
}
