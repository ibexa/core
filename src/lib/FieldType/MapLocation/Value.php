<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\MapLocation;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for MapLocation field type.
 */
class Value extends BaseValue
{
    /**
     * Latitude of the location.
     *
     * @var float|null
     */
    public $latitude;

    /**
     * Longitude of the location.
     *
     * @var float|null
     */
    public $longitude;

    /**
     * Display address for the location.
     *
     * @var string|null
     */
    public $address;

    /**
     * Construct a new Value object and initialize with $values.
     *
     * @param string[]|string $values
     */
    public function __construct(array $values = null)
    {
        foreach ((array)$values as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Returns a string representation of the keyword value.
     *
     * @return string A comma separated list of tags, eg: "php, Ibexa, html5"
     */
    public function __toString()
    {
        if (is_array($this->address)) {
            return implode(', ', $this->address);
        }

        return (string)$this->address;
    }
}
