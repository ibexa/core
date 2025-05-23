<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common;

use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use RuntimeException;

/**
 * Provides search backend field name resolving for criteria and sort clauses
 * targeting Content fields.
 */
class FieldNameResolver
{
    /**
     * Field registry.
     *
     * @var \Ibexa\Core\Search\Common\FieldRegistry
     */
    protected $fieldRegistry;

    /**
     * Content type handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler
     */
    protected $contentTypeHandler;

    /**
     * Field name generator.
     *
     * @var \Ibexa\Core\Search\Common\FieldNameGenerator
     */
    protected $nameGenerator;

    /**
     * Create from search field registry, content type handler and field name generator.
     *
     * @param \Ibexa\Core\Search\Common\FieldRegistry $fieldRegistry
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\Handler $contentTypeHandler
     * @param \Ibexa\Core\Search\Common\FieldNameGenerator $nameGenerator
     */
    public function __construct(
        FieldRegistry $fieldRegistry,
        ContentTypeHandler $contentTypeHandler,
        FieldNameGenerator $nameGenerator
    ) {
        $this->fieldRegistry = $fieldRegistry;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Get content type, field definition and field type mapping information.
     *
     * Returns an array in the form:
     *
     * <code>
     *  array(
     *      "<ContentType identifier>" => array(
     *          "<FieldDefinition identifier>" => array(
     *              "field_definition_id" => "<FieldDefinition id>",
     *              "field_type_identifier" => "<FieldType identifier>",
     *          ),
     *          ...
     *      ),
     *      ...
     *  )
     * </code>
     *
     * @return array[]
     */
    protected function getSearchableFieldMap()
    {
        return $this->contentTypeHandler->getSearchableFieldMap();
    }

    /**
     * For the given parameters returns a set of search backend field names/types to search on.
     *
     * The method will check for custom fields if given $criterion implements
     * CustomFieldInterface. With optional parameters $fieldTypeIdentifier and
     * $name specific field type and field from its Indexable implementation
     * can be targeted.
     *
     * @see \Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface
     * @see \Ibexa\Contracts\Core\FieldType\Indexable
     *
     * @param string $fieldDefinitionIdentifier
     * @param string|null $fieldTypeIdentifier
     * @param string|null $name
     *
     * @return array<string, \Ibexa\Contracts\Core\Search\FieldType>
     */
    public function getFieldTypes(
        CriterionInterface $criterion,
        $fieldDefinitionIdentifier,
        $fieldTypeIdentifier = null,
        $name = null
    ) {
        $fieldMap = $this->getSearchableFieldMap();
        $fieldTypeNameMap = [];

        foreach ($fieldMap as $contentTypeIdentifier => $fieldIdentifierMap) {
            // First check if field exists in the current ContentType, there is nothing to do if it doesn't
            if (!isset($fieldIdentifierMap[$fieldDefinitionIdentifier])) {
                continue;
            }

            // If $fieldTypeIdentifier is given it must match current field definition
            if (
                $fieldTypeIdentifier !== null &&
                $fieldTypeIdentifier !== $fieldIdentifierMap[$fieldDefinitionIdentifier]['field_type_identifier']
            ) {
                continue;
            }

            $fieldNameWithSearchType = $this->getIndexFieldName(
                $criterion,
                $contentTypeIdentifier,
                $fieldDefinitionIdentifier,
                $fieldIdentifierMap[$fieldDefinitionIdentifier]['field_type_identifier'],
                $name,
                false
            );

            $fieldNames = array_keys($fieldNameWithSearchType);
            $fieldName = reset($fieldNames);

            $fieldTypeNameMap[$fieldName] = $fieldNameWithSearchType[$fieldName];
        }

        return $fieldTypeNameMap;
    }

    /**
     * For the given parameters returns search backend field name to sort on or
     * null if the field could not be found.
     *
     * The method will check for custom fields if given $sortClause implements
     * CustomFieldInterface. With optional parameter $name specific field from
     * field type's Indexable implementation can be targeted.
     *
     * Will return null if no sortable field is found.
     *
     * @see \Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface
     * @see \Ibexa\Contracts\Core\FieldType\Indexable
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause
     * @param string $contentTypeIdentifier
     * @param string $fieldDefinitionIdentifier
     * @param string|null $name
     *
     * @return string|null
     */
    public function getSortFieldName(
        SortClause $sortClause,
        $contentTypeIdentifier,
        $fieldDefinitionIdentifier,
        $name = null
    ) {
        $fieldMap = $this->getSearchableFieldMap();

        // First check if field exists in type, there is nothing to do if it doesn't
        if (!isset($fieldMap[$contentTypeIdentifier][$fieldDefinitionIdentifier])) {
            return null;
        }

        $fieldName = array_keys($this->getIndexFieldName(
            $sortClause,
            $contentTypeIdentifier,
            $fieldDefinitionIdentifier,
            $fieldMap[$contentTypeIdentifier][$fieldDefinitionIdentifier]['field_type_identifier'],
            $name,
            true
        ));

        return reset($fieldName);
    }

    /**
     * Returns index field name for the given parameters.
     *
     * @param object $criterionOrSortClause
     * @param string $contentTypeIdentifier
     * @param string $fieldDefinitionIdentifier
     * @param string $fieldTypeIdentifier
     * @param string $name
     * @param bool $isSortField
     *
     * @return string
     */
    public function getIndexFieldName(
        $criterionOrSortClause,
        $contentTypeIdentifier,
        $fieldDefinitionIdentifier,
        $fieldTypeIdentifier,
        $name,
        $isSortField
    ) {
        // If criterion or sort clause implements CustomFieldInterface and custom field is set for
        // ContentType/FieldDefinition, return it
        if (
            $criterionOrSortClause instanceof CustomFieldInterface &&
            $customFieldName = $criterionOrSortClause->getCustomField(
                $contentTypeIdentifier,
                $fieldDefinitionIdentifier
            )
        ) {
            return [$customFieldName => null];
        }

        // Else, generate field name from field type's index definition

        $indexFieldType = $this->fieldRegistry->getType($fieldTypeIdentifier);

        // If $name is not given use default field name
        if ($name === null) {
            if ($isSortField) {
                $name = $indexFieldType->getDefaultSortField();
            } else {
                $name = $indexFieldType->getDefaultMatchField();
            }
        }

        $indexDefinition = $indexFieldType->getIndexDefinition();

        // Should only happen by mistake, so let's throw if it does
        if ($name === null) {
            throw new RuntimeException(
                "Undefined default sort or match field in '{$fieldTypeIdentifier}' Field Type's index definition"
            );
        }

        if (!isset($indexDefinition[$name])) {
            throw new RuntimeException(
                "Could not find Field '{$name}' in '{$fieldTypeIdentifier}' Field Type's index definition"
            );
        }

        $field = $this->nameGenerator->getTypedName(
            $this->nameGenerator->getName(
                $name,
                $fieldDefinitionIdentifier,
                $contentTypeIdentifier
            ),
            $indexDefinition[$name]
        );

        return [$field => $indexDefinition[$name]];
    }

    public function getAggregationFieldName(
        string $contentTypeIdentifier,
        string $fieldDefinitionIdentifier,
        string $name
    ): ?string {
        $fieldMap = $this->getSearchableFieldMap();

        // First check if field exists in type, there is nothing to do if it doesn't
        if (!isset($fieldMap[$contentTypeIdentifier][$fieldDefinitionIdentifier])) {
            return null;
        }

        $fieldName = array_keys(
            $this->getIndexFieldName(
                null,
                $contentTypeIdentifier,
                $fieldDefinitionIdentifier,
                $fieldMap[$contentTypeIdentifier][$fieldDefinitionIdentifier]['field_type_identifier'],
                $name,
                true
            )
        );

        return reset($fieldName);
    }
}
