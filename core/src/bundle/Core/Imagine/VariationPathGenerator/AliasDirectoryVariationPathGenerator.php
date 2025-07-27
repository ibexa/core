<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\VariationPathGenerator;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;

/**
 * Puts variations in the an _alias/<aliasName> subfolder.
 *
 * Example:
 * my/image/file.jpg -> _aliases/large/my/image/file.jpg
 */
class AliasDirectoryVariationPathGenerator implements VariationPathGenerator
{
    public function getVariationPath(string $path, string $variation): string
    {
        $info = pathinfo($path);

        return sprintf(
            '_aliases/%s/%s/%s%s',
            $variation,
            $info['dirname'],
            $info['filename'],
            empty($info['extension']) ? '' : '.' . $info['extension']
        );
    }
}
