<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\MapLocation;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the MapLocation field type.
 */
class Value extends BaseValue
{
    /**
     * Latitude of the location.
     */
    public readonly ?float $latitude;

    /**
     * Longitude of the location.
     */
    public readonly ?float $longitude;

    /**
     * Display address for the location.
     */
    public readonly ?string $address;

    /**
     * @param array{latitude: float|null, longitude: float|null, address: string|string[]|null}|null $values
     */
    public function __construct(?array $values = null)
    {
        if (null !== $values) {
            $this->latitude = $values['latitude'] ?? null;
            $this->longitude = $values['longitude'] ?? null;

            $address = is_array($values['address'] ?? null)
                ? implode(', ', $values['address'])
                : ($values['address'] ?? null);
            $this->address = $address;
        }

        parent::__construct();
    }

    public function __toString(): string
    {
        return (string)$this->address;
    }
}
