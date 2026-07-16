<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Mapper;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType;
use Ibexa\Core\Search\Common\FieldRegistry;
use Ibexa\Core\Search\Legacy\Content\FullTextData;
use Ibexa\Core\Search\Legacy\Content\FullTextValue;

/**
 * FullTextMapper maps Content object fields to FullTextValue objects which are searchable and
 * therefore can be indexed by the legacy search engine.
 */
class FullTextMapper
{
    /**
     * Field registry.
     *
     * @var FieldRegistry
     */
    protected $fieldRegistry;

    /**
     * Content type handler.
     *
     * @var Handler
     */
    protected $contentTypeHandler;

    /**
     * @param FieldRegistry $fieldRegistry
     * @param Handler $contentTypeHandler
     */
    public function __construct(
        FieldRegistry $fieldRegistry,
        ContentTypeHandler $contentTypeHandler
    ) {
        $this->fieldRegistry = $fieldRegistry;
        $this->contentTypeHandler = $contentTypeHandler;
    }

    /**
     * Map given Content to a FullTextValue.
     *
     * @param Content $content
     *
     * @return FullTextData
     */
    public function mapContent(Content $content)
    {
        return new FullTextData(
            [
                'id' => $content->versionInfo->contentInfo->id,
                'contentTypeId' => $content->versionInfo->contentInfo->contentTypeId,
                'sectionId' => $content->versionInfo->contentInfo->sectionId,
                'published' => $content->versionInfo->contentInfo->publicationDate,
                'values' => $this->getFullTextValues($content),
            ]
        );
    }

    /**
     * Returns an array of FullTextValue object containing searchable values of content object
     * fields for the given $content.
     *
     * @param Content $content
     *
     * @return FullTextValue[]
     *
     * @throws NotFoundException
     */
    protected function getFullTextValues(Content $content): array
    {
        $fullTextValues = [];
        foreach ($content->fields as $field) {
            $fieldDefinition = $this->contentTypeHandler->getFieldDefinition(
                $field->fieldDefinitionId,
                Type::STATUS_DEFINED
            );
            if (!$fieldDefinition->isSearchable) {
                continue;
            }

            $fullTextField = $this->extractFullTextField($field, $fieldDefinition);
            if (null === $fullTextField || empty($fullTextField->value)) {
                continue;
            }
            $fullTextValue = !is_array($fullTextField->value)
                ? $fullTextField->value
                : implode(' ', $fullTextField->value);

            $contentInfo = $content->versionInfo->contentInfo;
            $fullTextValues[] = new FullTextValue(
                [
                    'id' => $field->id,
                    'fieldDefinitionId' => $field->fieldDefinitionId,
                    'fieldDefinitionIdentifier' => $fieldDefinition->identifier,
                    'languageCode' => $field->languageCode,
                    'value' => $fullTextValue,
                    'isMainAndAlwaysAvailable' => (
                        $field->languageCode === $contentInfo->mainLanguageCode && $contentInfo->alwaysAvailable
                    ),
                    'transformationRules' => $fullTextField->type->transformationRules,
                    'splitFlag' => $fullTextField->type->splitFlag,
                ]
            );
        }

        return $fullTextValues;
    }

    private function extractFullTextField(
        Content\Field $field,
        Type\FieldDefinition $fieldDefinition
    ): ?Field {
        $fieldType = $this->fieldRegistry->getType($field->type);
        $fullTextFields = array_filter(
            $fieldType->getIndexData($field, $fieldDefinition),
            static function ($indexField): bool {
                return $indexField->type instanceof FieldType\FullTextField;
            }
        );

        return !empty($fullTextFields) ? array_values($fullTextFields)[0] : null;
    }
}
