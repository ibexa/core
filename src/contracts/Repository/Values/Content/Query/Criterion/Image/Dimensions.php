<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

/**
 * @phpstan-import-type Range from \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\AbstractImageCompositeCriterion
 *
 * @phpstan-type ImageCriteria array{
 *      width?: Range,
 *      height?: Range,
 * }
 *
 * @template-extends \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\AbstractImageCompositeCriterion<ImageCriteria>
 */
final class Dimensions extends AbstractImageCompositeCriterion
{
    public const IMAGE_DIMENSIONS_CRITERIA = [
        'width',
        'height',
    ];

    /**
     * @return array<string>
     */
    protected function getSupportedCriteria(): array
    {
        return self::IMAGE_DIMENSIONS_CRITERIA;
    }

    /**
     * @phpstan-param ImageCriteria $imageCriteriaData
     *
     * @return array<Criterion>
     *
     * @throws InvalidArgumentException
     */
    protected function buildCriteria(
        string $fieldDefIdentifier,
        array $imageCriteriaData
    ): array {
        $criteria = [];

        if (isset($imageCriteriaData['width'])) {
            $width = $imageCriteriaData['width'];
            $criteria[] = new Width(
                $fieldDefIdentifier,
                $this->getMinValue($width),
                $this->getMaxValue($width)
            );
        }

        if (isset($imageCriteriaData['height'])) {
            $height = $imageCriteriaData['height'];
            $criteria[] = new Height(
                $fieldDefIdentifier,
                $this->getMinValue($height),
                $this->getMaxValue($height)
            );
        }

        return $criteria;
    }
}
