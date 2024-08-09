<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\BinaryBase;

use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Search;

/**
 * @internal
 */
abstract class AbstractBinarySearchField implements Indexable
{
    /**
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function getIndexData(Field $field, FieldDefinition $fieldDefinition): array
    {
        return [
            new Search\Field(
                'file_name',
                $field->value->externalData['fileName'] ?? null,
                new Search\FieldType\StringField()
            ),
            new Search\Field(
                'file_size',
                $field->value->externalData['fileSize'] ?? null,
                new Search\FieldType\IntegerField()
            ),
            new Search\Field(
                'mime_type',
                $field->value->externalData['mimeType'] ?? null,
                new Search\FieldType\StringField()
            ),
        ];
    }

    /**
     * @return array<string, \Ibexa\Contracts\Core\Search\FieldType>
     */
    public function getIndexDefinition(): array
    {
        return [
            'file_name' => new Search\FieldType\StringField(),
            'file_size' => new Search\FieldType\IntegerField(),
            'mime_type' => new Search\FieldType\StringField(),
        ];
    }

    public function getDefaultMatchField(): string
    {
        return 'file_name';
    }

    public function getDefaultSortField(): string
    {
        return $this->getDefaultMatchField();
    }
}
