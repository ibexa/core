<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\Orientation as ImageOrientation;

final class Orientation extends Criterion
{
    private const ALLOWED_ORIENTATIONS = [
        ImageOrientation::SQUARE,
        ImageOrientation::PORTRAIT,
        ImageOrientation::LANDSCAPE,
    ];

    /**
     * @param string|array<string> $orientation
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $fieldDefIdentifier,
        string|array $orientation
    ) {
        $this->validate($orientation);

        parent::__construct($fieldDefIdentifier, null, $orientation);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_STRING
            ),
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_STRING
            ),
        ];
    }

    /**
     * @param string|array<string> $orientation
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    private function validate(string|array $orientation): void
    {
        if (
            is_string($orientation)
            && !$this->isSupportedOrientation($orientation)
        ) {
            $this->throwException($orientation);
        }

        if (is_array($orientation)) {
            $invalidOrientations = array_filter(
                $orientation,
                fn ($value): bool => !$this->isSupportedOrientation($value)
            );

            if (!empty($invalidOrientations)) {
                $this->throwException(implode(', ', $invalidOrientations));
            }
        }
    }

    private function isSupportedOrientation(string $orientation): bool
    {
        return in_array($orientation, self::ALLOWED_ORIENTATIONS, true);
    }

    /**
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    private function throwException(string $whatIsWrong): never
    {
        throw new InvalidArgumentException(
            '$orientation',
            sprintf(
                'Invalid image orientation: "%s". Allowed orientations: %s',
                $whatIsWrong,
                implode(', ', self::ALLOWED_ORIENTATIONS)
            )
        );
    }
}
