<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\Helper\FieldsGroups\FieldsGroupsList;
use Ibexa\Core\Helper\TranslationHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig content extension for Ibexa specific usage.
 * Exposes helpers to play with public API objects.
 */
class ContentExtension extends AbstractExtension
{
    /** @var \Ibexa\Contracts\Core\Repository\Repository */
    protected $repository;

    /** @var \Ibexa\Core\Helper\TranslationHelper */
    protected $translationHelper;

    /** @var \Ibexa\Core\Helper\FieldHelper */
    protected $fieldHelper;

    private FieldsGroupsList $fieldsGroupsList;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    public function __construct(
        Repository $repository,
        TranslationHelper $translationHelper,
        FieldHelper $fieldHelper,
        FieldsGroupsList $fieldsGroupsList,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->translationHelper = $translationHelper;
        $this->fieldHelper = $fieldHelper;
        $this->fieldsGroupsList = $fieldsGroupsList;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Returns a list of functions to add to the existing list.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ibexa_content_name',
                $this->getTranslatedContentName(...)
            ),
            new TwigFunction(
                'ibexa_field_value',
                $this->getTranslatedFieldValue(...)
            ),
            new TwigFunction(
                'ibexa_field',
                $this->getTranslatedField(...)
            ),
            new TwigFunction(
                'ibexa_has_field',
                $this->hasField(...)
            ),
            new TwigFunction(
                'ibexa_field_is_empty',
                $this->isFieldEmpty(...)
            ),
            new TwigFunction(
                'ibexa_field_name',
                $this->getTranslatedFieldDefinitionName(...)
            ),
            new TwigFunction(
                'ibexa_field_description',
                $this->getTranslatedFieldDefinitionDescription(...)
            ),
            new TwigFunction(
                'ibexa_field_group_name',
                $this->getFieldGroupName(...)
            ),
            new TwigFunction(
                'ibexa_content_field_identifier_first_filled_image',
                $this->getFirstFilledImageFieldIdentifier(...)
            ),
        ];
    }

    /**
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType When $content is not a valid Content, ContentInfo, or ContentAwareInterface object.
     *
     * @return string
     */
    public function getTranslatedContentName(Content|ContentInfo|ContentAwareInterface $data, $forcedLanguage = null)
    {
        $content = $this->resolveData($data);
        if ($content instanceof Content) {
            return $this->translationHelper->getTranslatedContentName($content, $forcedLanguage);
        } elseif ($content instanceof ContentInfo) {
            return $this->translationHelper->getTranslatedContentNameByContentInfo($content, $forcedLanguage);
        }
    }

    /**
     * Returns the translated field, very similar to getTranslatedFieldValue but this returns the whole field.
     * To be used with ibexa_image_alias for example, which requires the whole field.
     *
     * @param string $fieldDefIdentifier Identifier for the field we want to get.
     * @param string $forcedLanguage Locale we want the field in (e.g. "cro-HR"). Null by default (takes current locale).
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field
     */
    public function getTranslatedField(Content|ContentAwareInterface $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        return $this->translationHelper->getTranslatedField($this->getContent($data), $fieldDefIdentifier, $forcedLanguage);
    }

    /**
     * @param string $fieldDefIdentifier Identifier for the field we want to get the value from.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale).
     *
     * @return mixed A primitive type or a field type Value object depending on the field type.
     */
    public function getTranslatedFieldValue(Content|ContentAwareInterface $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        return $this->translationHelper->getTranslatedField($this->getContent($data), $fieldDefIdentifier, $forcedLanguage)->value;
    }

    /**
     * Gets name of a FieldDefinition name by loading ContentType based on Content/ContentInfo/ContentAwareInterface object.
     *
     * @param string $fieldDefIdentifier Identifier for the field we want to get the name from
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType When $content is not a valid Content, ContentInfo, or ContentAwareInterface object.
     *
     * @return string|null
     */
    public function getTranslatedFieldDefinitionName(Content|ContentInfo|ContentAwareInterface $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        if ($contentType = $this->getContentType($this->resolveData($data))) {
            return $this->translationHelper->getTranslatedFieldDefinitionProperty(
                $contentType,
                $fieldDefIdentifier,
                'name',
                $forcedLanguage
            );
        }
    }

    /**
     * Gets name of a FieldDefinition description by loading ContentType based on Content/ContentInfo/ContentAwareInterface object.
     *
     * @param string $fieldDefIdentifier Identifier for the field we want to get the name from
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @return string|null
     */
    public function getTranslatedFieldDefinitionDescription(Content|ContentInfo|ContentAwareInterface $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        if ($contentType = $this->getContentType($this->resolveData($data))) {
            return $this->translationHelper->getTranslatedFieldDefinitionProperty(
                $contentType,
                $fieldDefIdentifier,
                'description',
                $forcedLanguage
            );
        }
    }

    public function hasField(Content|ContentAwareInterface $data, string $fieldDefIdentifier): bool
    {
        $content = $this->getContent($data);

        return $content->getContentType()->hasFieldDefinition($fieldDefIdentifier);
    }

    public function getFieldGroupName(string $identifier): ?string
    {
        return $this->fieldsGroupsList->getGroups()[$identifier] ?? null;
    }

    /**
     * Checks if a given field is considered empty.
     * This method accepts field as Objects or by identifiers.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field|string $fieldDefIdentifier Field or Field Identifier to
     *                                                                                   get the value from.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR").
     *                               Null by default (takes current locale).
     *
     * @return bool
     */
    public function isFieldEmpty(Content|ContentAwareInterface $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        if ($fieldDefIdentifier instanceof Field) {
            $fieldDefIdentifier = $fieldDefIdentifier->fieldDefIdentifier;
        }

        return $this->fieldHelper->isFieldEmpty($this->getContent($data), $fieldDefIdentifier, $forcedLanguage);
    }

    /**
     * Get ContentType by Content/ContentInfo.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType|null
     */
    private function getContentType(Content|ContentInfo $content)
    {
        if ($content instanceof Content) {
            return $this->repository->getContentTypeService()->loadContentType(
                $content->getVersionInfo()->getContentInfo()->contentTypeId
            );
        }

        return $this->repository->getContentTypeService()->loadContentType($content->contentTypeId);
    }

    public function getFirstFilledImageFieldIdentifier(Content|ContentAwareInterface $data)
    {
        $content = $this->getContent($data);
        foreach ($content->getFieldsByLanguage() as $field) {
            $fieldTypeIdentifier = $content->getContentType()
                ->getFieldDefinition($field->fieldDefIdentifier)
                ->fieldTypeIdentifier;

            if ($fieldTypeIdentifier !== 'ibexa_image') {
                continue;
            }

            if ($this->fieldHelper->isFieldEmpty($content, $field->fieldDefIdentifier)) {
                continue;
            }

            return $field->fieldDefIdentifier;
        }

        return null;
    }

    private function resolveData(Content|ContentInfo|ContentAwareInterface $data): ValueObject
    {
        if ($data instanceof Content || $data instanceof ContentInfo) {
            return $data;
        }

            return $data->getContent();
    }

    private function getContent(Content|ContentAwareInterface $data): Content
    {
        if ($data instanceof Content) {
            return $data;
        }

            return $data->getContent();
    }
}
