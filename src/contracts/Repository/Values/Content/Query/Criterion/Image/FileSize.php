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
        $minValue = null,
        $maxValue = null
    ) {
        $minValue = $this->convertToBytes($minValue);
        $maxValue = $this->convertToBytes($maxValue);

        parent::__construct(
            $fieldDefIdentifier,
            $minValue,
            $maxValue
        );
    }

    /**
     * @param numeric|null $value
     */
    private function convertToBytes($value): ?int
    {
        if (
            null === $value
            || 0 === $value
        ) {
            return null;
        }

        $value *= 1024 * 1024;

        return (int)$value;
    }
}
