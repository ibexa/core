<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Token;

use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;

class WebSafeGenerator implements TokenGeneratorInterface
{
    /**
     * @throws \Exception
     */
    public function generateToken(int $length = 64): string
    {
        $entropy = floor(($length + 1) * 0.75);
        $encoded = base64_encode(random_bytes($entropy));

        return substr(rtrim(strtr($encoded, '+-', '/_'), '='), 0, $length);
    }
}
