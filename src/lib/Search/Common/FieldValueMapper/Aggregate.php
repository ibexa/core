<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common aggregate mapper implementation.
 */
class Aggregate extends FieldValueMapper
{
    /**
     * Array of available mappers.
     *
     * @var \Ibexa\Core\Search\Common\FieldValueMapper[]
     */
    protected $mappers = [];

    /**
     * Array of simple mappers mapping specific Field (by its FQCN).
     *
     * @var array<string, \Ibexa\Core\Search\Common\FieldValueMapper>
     */
    protected $simpleMappers = [];

    /**
     * Construct from optional mapper array.
     *
     * @param \Ibexa\Core\Search\Common\FieldValueMapper[] $mappers
     */
    public function __construct(array $mappers = [])
    {
        foreach ($mappers as $mapper) {
            $this->addMapper($mapper);
        }
    }

    /**
     * @param class-string<\Ibexa\Contracts\Core\Search\FieldType>|null $searchTypeFQCN
     */
    public function addMapper(FieldValueMapper $mapper, ?string $searchTypeFQCN = null): void
    {
        if (null !== $searchTypeFQCN) {
            $this->simpleMappers[$searchTypeFQCN] = $mapper;
        } else {
            $this->mappers[] = $mapper;
        }
    }

    public function canMap(Field $field): bool
    {
        return true;
    }

    /**
     * Map field value to a proper search engine representation.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     *
     * @param \Ibexa\Contracts\Core\Search\Field $field
     *
     * @return mixed
     */
    public function map(Field $field)
    {
        $mapper = $this->simpleMappers[get_class($field->getType())]
            ?? $this->findMapper($field);

        return $mapper->map($field);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    private function findMapper(Field $field): FieldValueMapper
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->canMap($field)) {
                return $mapper;
            }
        }

        throw new NotImplementedException(
            'No mapper available for: ' . get_class($field->getType())
        );
    }
}

class_alias(Aggregate::class, 'eZ\Publish\Core\Search\Common\FieldValueMapper\Aggregate');
