<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine;

use Ibexa\Core\FieldType\Image\Value as ImageValue;

interface PlaceholderProvider
{
    /**
     * Provides a placeholder image path for a given Image FieldType value.
     *
     * @param array<string, mixed> $options
     *
     * @return string Path to placeholder
     */
    public function getPlaceholder(ImageValue $value, array $options = []): string;
}
