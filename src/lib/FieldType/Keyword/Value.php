<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Keyword;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Keyword field type.
 */
class Value extends BaseValue
{
    /**
     * Content of the value.
     *
     * @var string[]
     */
    public readonly array $values;

    /**
     * Construct a new Value object and initialize with $values.
     *
     * @param string[]|string $values either an array of keywords or a comma-separated list of keywords
     */
    public function __construct(array|string|null $values = null)
    {
        if ($values !== null) {
            if (!is_array($values)) {
                $tags = [];
                foreach (explode(',', $values) as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $tags[] = $tag;
                    }
                }
                $values = $tags;
            }

            $this->values = array_unique($values);
        } else {
            $this->values = [];
        }

        parent::__construct();
    }

    /**
     * Returns a string representation of the keyword value.
     *
     * @return string A comma separated list of tags, eg: "php, Ibexa, html5"
     */
    public function __toString(): string
    {
        return implode(', ', $this->values);
    }
}
