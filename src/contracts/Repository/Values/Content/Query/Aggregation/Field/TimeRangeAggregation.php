<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface;
use Traversable;

/**
 * @phpstan-extends \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field\AbstractFieldRangeAggregation<int>
 */
final class TimeRangeAggregation extends AbstractFieldRangeAggregation
{
    /**
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface<int> $generator
     */
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
