<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

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
     * @phpstan-param ImageCriteria $data
     *
     * @return array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function buildCriteria(
        string $fieldDefIdentifier,
        array $data
    ): array {
        $criteria = [];

        if (isset($data['width'])) {
            $width = $data['width'];
            $criteria[] = new Width(
                $fieldDefIdentifier,
                $this->getMinValue($width),
                $this->getMaxValue($width)
            );
        }

        if (isset($data['height'])) {
            $height = $data['height'];
            $criteria[] = new Height(
                $fieldDefIdentifier,
                $this->getMinValue($height),
                $this->getMaxValue($height)
            );
        }

        return $criteria;
    }
}
