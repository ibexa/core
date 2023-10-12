<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

final class FileSize extends AbstractImageRangeCriterion
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $fieldDefIdentifier,
        int $minFileSize = 0,
        ?int $maxFileSize = null
    ) {
        if ($minFileSize > 0) {
            $minFileSize *= 1024 * 1024;
        }

        if ($maxFileSize > 0) {
            $maxFileSize *= 1024 * 1024;
        }

        parent::__construct(
            $fieldDefIdentifier,
            $minFileSize,
            $maxFileSize
        );
    }
}
