<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface;
use Traversable;

/**
 * @phpstan-extends \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\AbstractRangeAggregation<\DateTimeInterface>
 */
final class DateMetadataRangeAggregation extends AbstractRangeAggregation
{
    public const string MODIFIED = 'modified';
    public const string CREATED = 'created';
    public const string PUBLISHED = 'published';

    private string $type;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<covariant \DateTimeInterface>[] $ranges
     */
    public function __construct(
        string $name,
        string $type,
        array $ranges = []
    ) {
        parent::__construct($name, $ranges);
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface<covariant \DateTimeInterface> $generator
     */
    public static function fromGenerator(
        string $name,
        string $type,
        RangesGeneratorInterface $generator
    ): self {
        $ranges = $generator->generate();
        if ($ranges instanceof Traversable) {
            $ranges = iterator_to_array($ranges);
        }

        return new self($name, $type, $ranges);
    }
}
