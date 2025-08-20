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
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
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
    use DeprecationOptionsTrait;

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
                'ez_content_name',
                [$this, 'getTranslatedContentName'],
                $this->getDeprecationOptions('ibexa_content_name'),
            ),
            new TwigFunction(
                'ibexa_content_name',
                [$this, 'getTranslatedContentName']
            ),
            new TwigFunction(
                'ez_field_value',
                [$this, 'getTranslatedFieldValue'],
                $this->getDeprecationOptions('ibexa_field_value'),
            ),
            new TwigFunction(
                'ibexa_field_value',
                [$this, 'getTranslatedFieldValue']
            ),
            new TwigFunction(
                'ez_field',
                [$this, 'getTranslatedField'],
                $this->getDeprecationOptions('ibexa_field'),
            ),
            new TwigFunction(
                'ibexa_field',
                [$this, 'getTranslatedField']
            ),
            new TwigFunction(
                'ez_field_is_empty',
                [$this, 'isFieldEmpty'],
                $this->getDeprecationOptions('ibexa_field_is_empty'),
            ),
            new TwigFunction(
                'ibexa_has_field',
                [$this, 'hasField']
            ),
            new TwigFunction(
                'ibexa_field_is_empty',
                [$this, 'isFieldEmpty']
            ),
            new TwigFunction(
                'ez_field_name',
                [$this, 'getTranslatedFieldDefinitionName'],
                $this->getDeprecationOptions('ibexa_field_name'),
            ),
            new TwigFunction(
                'ibexa_field_name',
                [$this, 'getTranslatedFieldDefinitionName']
            ),
            new TwigFunction(
                'ez_field_description',
                [$this, 'getTranslatedFieldDefinitionDescription'],
                $this->getDeprecationOptions('ibexa_field_description'),
            ),
            new TwigFunction(
                'ibexa_field_description',
                [$this, 'getTranslatedFieldDefinitionDescription']
            ),
            new TwigFunction(
                'ibexa_field_group_name',
                [$this, 'getFieldGroupName']
            ),
            new TwigFunction(
                'ez_content_field_identifier_first_filled_image',
                [$this, 'getFirstFilledImageFieldIdentifier'],
                $this->getDeprecationOptions('ibexa_content_field_identifier_first_filled_image'),
            ),
            new TwigFunction(
                'ibexa_content_field_identifier_first_filled_image',
                [$this, 'getFirstFilledImageFieldIdentifier']
            ),
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data Must be a valid Content, ContentInfo, or ContentAwareInterface object.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType When $content is not a valid Content, ContentInfo, or ContentAwareInterface object.
     *
     * @return string
     */
    public function getTranslatedContentName(object $data, $forcedLanguage = null)
    {
        $content = $this->resolveData($data);
        if ($content instanceof Content) {
            return $this->translationHelper->getTranslatedContentName($content, $forcedLanguage);
        } elseif ($content instanceof ContentInfo) {
            return $this->translationHelper->getTranslatedContentNameByContentInfo($content, $forcedLanguage);
        }

        throw new InvalidArgumentType(
            '$data',
            sprintf('%s or %s or %s', Content::class, ContentInfo::class, ContentAwareInterface::class),
            $data
        );
    }

    /**
     * Returns the translated field, very similar to getTranslatedFieldValue but this returns the whole field.
     * To be used with ibexa_image_alias for example, which requires the whole field.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     * @param string $fieldDefIdentifier Identifier for the field we want to get.
     * @param string $forcedLanguage Locale we want the field in (e.g. "cro-HR"). Null by default (takes current locale).
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field
     */
    public function getTranslatedField(object $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        return $this->translationHelper->getTranslatedField($this->getContent($data), $fieldDefIdentifier, $forcedLanguage);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     * @param string $fieldDefIdentifier Identifier for the field we want to get the value from.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale).
     *
     * @return mixed A primitive type or a field type Value object depending on the field type.
     */
    public function getTranslatedFieldValue(object $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        return $this->translationHelper->getTranslatedField($this->getContent($data), $fieldDefIdentifier, $forcedLanguage)->value;
    }

    /**
     * Gets name of a FieldDefinition name by loading ContentType based on Content/ContentInfo/ContentAwareInterface object.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data Must be Content, ContentInfo, or ContentAwareInterface object
     * @param string $fieldDefIdentifier Identifier for the field we want to get the name from
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType When $content is not a valid Content, ContentInfo, or ContentAwareInterface object.
     *
     * @return string|null
     */
    public function getTranslatedFieldDefinitionName(object $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        if ($contentType = $this->getContentType($this->resolveData($data))) {
            return $this->translationHelper->getTranslatedFieldDefinitionProperty(
                $contentType,
                $fieldDefIdentifier,
                'name',
                $forcedLanguage
            );
        }

        throw new InvalidArgumentType(
            '$data',
            sprintf('%s or %s or %s', Content::class, ContentInfo::class, ContentAwareInterface::class),
            $data
        );
    }

    /**
     * Gets name of a FieldDefinition description by loading ContentType based on Content/ContentInfo/ContentAwareInterface object.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data Must be Content, ContentInfo, or ContentAwareInterface object
     * @param string $fieldDefIdentifier Identifier for the field we want to get the name from
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @return string|null
     */
    public function getTranslatedFieldDefinitionDescription(object $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        if ($contentType = $this->getContentType($this->resolveData($data))) {
            return $this->translationHelper->getTranslatedFieldDefinitionProperty(
                $contentType,
                $fieldDefIdentifier,
                'description',
                $forcedLanguage
            );
        }

        throw new InvalidArgumentType(
            '$data',
            sprintf('%s or %s or %s', Content::class, ContentInfo::class, ContentAwareInterface::class),
            $data
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     */
    public function hasField(object $data, string $fieldDefIdentifier): bool
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
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field|string $fieldDefIdentifier Field or Field Identifier to
     *                                                                                   get the value from.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR").
     *                               Null by default (takes current locale).
     *
     * @return bool
     */
    public function isFieldEmpty(object $data, $fieldDefIdentifier, $forcedLanguage = null)
    {
        if ($fieldDefIdentifier instanceof Field) {
            $fieldDefIdentifier = $fieldDefIdentifier->fieldDefIdentifier;
        }

        return $this->fieldHelper->isFieldEmpty($this->getContent($data), $fieldDefIdentifier, $forcedLanguage);
    }

    /**
     * Get ContentType by Content/ContentInfo.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType|null
     */
    private function getContentType(ValueObject $content)
    {
        if ($content instanceof Content) {
            return $this->repository->getContentTypeService()->loadContentType(
                $content->getVersionInfo()->getContentInfo()->contentTypeId
            );
        } elseif ($content instanceof ContentInfo) {
            return $this->repository->getContentTypeService()->loadContentType($content->contentTypeId);
        }
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     */
    public function getFirstFilledImageFieldIdentifier(object $data)
    {
        $content = $this->getContent($data);
        foreach ($content->getFieldsByLanguage() as $field) {
            $fieldTypeIdentifier = $content->getContentType()
                ->getFieldDefinition($field->fieldDefIdentifier)
                ->fieldTypeIdentifier;

            if ($fieldTypeIdentifier !== 'ezimage') {
                continue;
            }

            if ($this->fieldHelper->isFieldEmpty($content, $field->fieldDefIdentifier)) {
                continue;
            }

            return $field->fieldDefIdentifier;
        }

        return null;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    private function resolveData(object $data): ValueObject
    {
        if ($data instanceof Content || $data instanceof ContentInfo) {
            return $data;
        }

        if ($data instanceof ContentAwareInterface) {
            return $data->getContent();
        }

        throw new InvalidArgumentType(
            '$content',
            sprintf('%s or %s or %s', Content::class, ContentInfo::class, ContentAwareInterface::class),
            $data,
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|\Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface $data
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    private function getContent(object $data): Content
    {
        if ($data instanceof Content) {
            return $data;
        }

        if ($data instanceof ContentAwareInterface) {
            return $data->getContent();
        }

        throw new InvalidArgumentType(
            '$content',
            sprintf('%s or %s', Content::class, ContentAwareInterface::class),
            $data,
        );
    }
}

class_alias(ContentExtension::class, 'eZ\Publish\Core\MVC\Symfony\Templating\Twig\Extension\ContentExtension');
