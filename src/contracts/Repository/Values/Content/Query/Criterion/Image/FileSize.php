<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

final class FileSize extends AbstractImageRangeCriterion
{
    public function __construct(
        string $fieldDefIdentifier,
        $minValue = 0,
        $maxValue = null
    ) {
        if ($minValue > 0) {
            $minValue *= 1024 * 1024;
        }

        if ($maxValue > 0) {
            $maxValue *= 1024 * 1024;
        }

        parent::__construct(
            $fieldDefIdentifier,
            $minValue,
            $maxValue
        );
    }
}
