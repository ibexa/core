<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\VariationPathGenerator;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;

/**
 * Puts variations in the same folder than the original, suffixed with the filter name:.
 *
 * Example:
 * my/image/file.jpg -> my/image/file_large.jpg
 */
class OriginalDirectoryVariationPathGenerator implements VariationPathGenerator
{
    public function getVariationPath(string $path, string $variation): string
    {
        $info = pathinfo($path);

        return sprintf(
            '%s/%s_%s%s',
            $info['dirname'],
            $info['filename'],
            $variation,
            empty($info['extension']) ? '' : '.' . $info['extension']
        );
    }
}
