<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\AbstractImageCompositeCriterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\FileSize;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\Height;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\MimeType;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\Orientation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\Width;

/**
 * @phpstan-import-type Range from \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\AbstractImageCompositeCriterion
 *
 * @phpstan-type ImageCriteria array{
 *      mimeTypes?: string|array<string>,
 *      size?: Range,
 *      width?: Range,
 *      height?: Range,
 *      orientation?: string|array<string>,
 * }
 *
 * @template-extends \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image\AbstractImageCompositeCriterion<ImageCriteria>
 */
final class Image extends AbstractImageCompositeCriterion
{
    public const IMAGE_SEARCH_CRITERIA = [
        'mimeTypes',
        'size',
        'width',
        'height',
        'orientation',
    ];

    protected function getSupportedCriteria(): array
    {
        return self::IMAGE_SEARCH_CRITERIA;
    }

    /**
     * @phpstan-param ImageCriteria $imageCriteriaData
     *
     * @return array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function buildCriteria(
        string $fieldDefIdentifier,
        array $imageCriteriaData
    ): array {
        $criteria = [];

        if (isset($imageCriteriaData['mimeTypes'])) {
            $criteria[] = new MimeType(
                $fieldDefIdentifier,
                $imageCriteriaData['mimeTypes']
            );
        }

        if (isset($imageCriteriaData['size'])) {
            $size = $imageCriteriaData['size'];
            $criteria[] = new FileSize(
                $fieldDefIdentifier,
                $this->getMinValue($size),
                $this->getMaxValue($size),
            );
        }

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

        if (isset($imageCriteriaData['orientation'])) {
            $criteria[] = new Orientation(
                $fieldDefIdentifier,
                $imageCriteriaData['orientation']
            );
        }

        return $criteria;
    }
}
