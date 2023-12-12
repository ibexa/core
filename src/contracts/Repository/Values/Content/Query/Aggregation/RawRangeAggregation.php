<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface;
use Traversable;

final class RawRangeAggregation extends AbstractRangeAggregation implements RawAggregation
{
    /** @var string */
    private $fieldName;

    public function __construct(string $name, string $fieldName, array $ranges = [])
    {
        parent::__construct($name, $ranges);

        $this->fieldName = $fieldName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public static function fromGenerator(
        string $name,
        string $fieldName,
        RangesGeneratorInterface $generator
    ): self {
        $ranges = $generator->generate();
        if ($ranges instanceof Traversable) {
            $ranges = iterator_to_array($ranges);
        }

        return new self($name, $fieldName, $ranges);
    }
}

class_alias(RawRangeAggregation::class, 'eZ\Publish\API\Repository\Values\Content\Query\Aggregation\RawRangeAggregation');
