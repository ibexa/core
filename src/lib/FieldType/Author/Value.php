<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Author;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Author field type.
 */
class Value extends BaseValue
{
    /**
     * List of authors.
     */
    public readonly AuthorCollection $authors;

    /**
     * Construct a new Value object and initialize with $authors.
     *
     * @param \Ibexa\Core\FieldType\Author\Author[] $authors
     */
    public function __construct(array $authors = [])
    {
        $this->authors = new AuthorCollection($authors);

        parent::__construct();
    }

    public function __toString(): string
    {
        if ($this->authors->count() <= 0) {
            return '';
        }

        $authorNames = [];
        foreach ($this->authors as $author) {
            $authorNames[] = $author->name;
        }

        return implode(', ', $authorNames);
    }
}
