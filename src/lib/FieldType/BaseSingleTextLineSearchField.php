<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Search;
use Ibexa\Contracts\Core\Search\FieldType;

abstract class BaseSingleTextLineSearchField implements Indexable
{
    /**
     * @return Search\Field[]
     */
    public function getIndexData(
        Field $field,
        FieldDefinition $fieldDefinition
    ): array {
        return [
            new Search\Field(
                'value',
                $field->value->data,
                new FieldType\StringField()
            ),
            new Search\Field(
                'fulltext',
                $field->value->data,
                new FieldType\FullTextField()
            ),
        ];
    }

    /**
     * @return array<string, FieldType>
     */
    public function getIndexDefinition(): array
    {
        return [
            'value' => new FieldType\StringField(),
        ];
    }

    public function getDefaultMatchField(): string
    {
        return 'value';
    }

    public function getDefaultSortField(): string
    {
        return $this->getDefaultMatchField();
    }
}
