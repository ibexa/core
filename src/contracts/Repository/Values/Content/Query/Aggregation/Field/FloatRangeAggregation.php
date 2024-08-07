<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface;
use Traversable;

final class FloatRangeAggregation extends AbstractFieldRangeAggregation
{
    public static function fromGenerator(
        string $name,
        string $contentTypeIdentifier,
        string $fieldDefinitionIdentifier,
        RangesGeneratorInterface $generator
    ): self {
        $ranges = $generator->generate();
        if ($ranges instanceof Traversable) {
            $ranges = iterator_to_array($ranges);
        }

        return new self($name, $contentTypeIdentifier, $fieldDefinitionIdentifier, $ranges);
    }
}

class_alias(FloatRangeAggregation::class, 'eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\FloatRangeAggregation');
