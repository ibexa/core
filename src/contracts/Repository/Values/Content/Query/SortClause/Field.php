<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target\FieldTarget;

/**
 * Sets sort direction on a field value for a content query.
 *
 * Note: for fields of some field types order will vary per search engine. This comes from the
 * different way of storing IDs in the search backend, and therefore relates to the field types
 * that store ID value for sorting (Relation field type). For Legacy search engine IDs are stored as
 * integers, while with Solr search engine they are stored as strings. In that case the
 * difference will be basically the one between numerical and alphabetical order of sorting.
 *
 * This reflects API definition of IDs as mixed type (integer or string).
 */
class Field extends SortClause implements CustomFieldInterface
{
    /**
     * Custom fields to sort by instead of the default field.
     *
     * @var array<string, array<string, string>>
     */
    protected array $customFields = [];

    /**
     * Constructs a new Field SortClause on Type $typeIdentifier and Field $fieldIdentifier.
     */
    public function __construct(string $typeIdentifier, string $fieldIdentifier, string $sortDirection = Query::SORT_ASC)
    {
        parent::__construct(
            'field',
            $sortDirection,
            new FieldTarget($typeIdentifier, $fieldIdentifier)
        );
    }

    /**
     * Set a custom field to sort by.
     *
     * Set a custom field to sort by for a defined field in a defined type.
     */
    public function setCustomField(string $type, string $field, string $customField): void
    {
        $this->customFields[$type][$field] = $customField;
    }

    /**
     * Return custom field.
     *
     * If no custom field is set, return null
     */
    public function getCustomField(string $type, string $field): ?string
    {
        return $this->customFields[$type][$field] ?? null;
    }
}
