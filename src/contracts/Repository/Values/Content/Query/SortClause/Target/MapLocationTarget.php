<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

/**
 * Struct that stores extra value information for a MapLocationDistance SortClause object.
 */
class MapLocationTarget extends Target
{
    /**
     * Latitude of a geographical location.
     */
    public float $latitude;

    /**
     * Longitude of a geographical location.
     */
    public float $longitude;

    /**
     * Identifier of a targeted Field ContentType.
     */
    public string $typeIdentifier;

    /**
     * Identifier of a targeted Field FieldDefinition.
     */
    public string $fieldIdentifier;

    public function __construct(
        float $latitude,
        float $longitude,
        string $typeIdentifier,
        string $fieldIdentifier
    ) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->typeIdentifier = $typeIdentifier;
        $this->fieldIdentifier = $fieldIdentifier;
    }
}
