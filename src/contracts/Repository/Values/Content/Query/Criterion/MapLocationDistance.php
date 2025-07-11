<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Value\MapLocationValue;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface;

/**
 * The MapLocationDistance Criterion class.
 *
 * Provides content filtering based on distance from geographical location.
 */
class MapLocationDistance extends Criterion implements CustomFieldInterface
{
    /**
     * Custom field definitions to query instead of default field.
     *
     * @var array<string, array<string, string>>
     */
    protected array $customFields = [];

    /**
     * @param string $target FieldDefinition identifier
     * @param string $operator One of the supported Operator constants
     * @param float|float[] $distance The match value in kilometers, either as an array
     *                                or as a single value, depending on the operator
     * @param float $latitude Latitude of the location that distance is calculated from
     * @param float $longitude Longitude of the location that distance is calculated from
     */
    public function __construct(string $target, string $operator, float|array $distance, float $latitude, float $longitude)
    {
        $distanceStart = new MapLocationValue($latitude, $longitude);
        parent::__construct($target, $operator, $distance, $distanceStart);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::IN, Specifications::FORMAT_ARRAY),
            new Specifications(Operator::EQ, Specifications::FORMAT_SINGLE),
            new Specifications(Operator::GT, Specifications::FORMAT_SINGLE),
            new Specifications(Operator::GTE, Specifications::FORMAT_SINGLE),
            new Specifications(Operator::LT, Specifications::FORMAT_SINGLE),
            new Specifications(Operator::LTE, Specifications::FORMAT_SINGLE),
            new Specifications(Operator::BETWEEN, Specifications::FORMAT_ARRAY, null, 2),
        ];
    }

    /**
     * Set a custom field to query.
     *
     * Set a custom field to query for a defined field in a defined type.
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
