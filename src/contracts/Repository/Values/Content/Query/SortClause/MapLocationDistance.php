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
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target\MapLocationTarget;

/**
 * Sets sort direction on the MapLocation distance for a content query.
 */
class MapLocationDistance extends SortClause implements CustomFieldInterface
{
    /**
     * Custom fields to sort by instead of the default field.
     *
     * @var array<string, array<string, string>>
     */
    protected array $customFields = [];

    /**
     * Constructs a new MapLocationDistance SortClause on Type $typeIdentifier and Field $fieldIdentifier.
     *
     * @param string $typeIdentifier ContentType identifier
     * @param string $fieldIdentifier FieldDefinition identifier
     * @param float $latitude Latitude of the location that distance is calculated from
     * @param float $longitude Longitude of the location that distance is calculated from
     * @param string $sortDirection
     */
    public function __construct(
        string $typeIdentifier,
        string $fieldIdentifier,
        float $latitude,
        float $longitude,
        string $sortDirection = Query::SORT_ASC
    ) {
        parent::__construct(
            'maplocation_distance',
            $sortDirection,
            new MapLocationTarget(
                $latitude,
                $longitude,
                $typeIdentifier,
                $fieldIdentifier
            )
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
