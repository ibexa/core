<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Country;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for a Country field type.
 *
 * @phpstan-type TCountriesHash array<string, array{Name: string, Alpha2: string, Alpha3: string, IDC: int}>
 */
class Value extends BaseValue
{
    /**
     * @phpstan-param TCountriesHash $countries
     *
     * Example Country hash entry:
     * ```
     *    [
     *        "JP" => [
     *            "Name" => "Japan",
     *            "Alpha2" => "JP",
     *            "Alpha3" => "JPN",
     *            "IDC" => 81
     *        ]
     *    ]
     * ```
     */
    public function __construct(public readonly array $countries = [])
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return implode(', ', array_column($this->countries, 'Name'));
    }
}
