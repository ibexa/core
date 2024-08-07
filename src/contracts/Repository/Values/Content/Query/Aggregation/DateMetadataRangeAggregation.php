<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface;
use Traversable;

final class DateMetadataRangeAggregation extends AbstractRangeAggregation
{
    public const MODIFIED = 'modified';
    public const CREATED = 'created';
    public const PUBLISHED = 'published';

    /** @var string */
    private $type;

    public function __construct(string $name, string $type, array $ranges = [])
    {
        parent::__construct($name, $ranges);
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

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

class_alias(DateMetadataRangeAggregation::class, 'eZ\Publish\API\Repository\Values\Content\Query\Aggregation\DateMetadataRangeAggregation');
