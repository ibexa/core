<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Token;

use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;

final class RandomBytesGenerator implements TokenGeneratorInterface
{
    /**
     * @throws \Exception
     */
    public function generateToken(int $length = 64): string
    {
        $entropy = (int)floor(($length + 1) * 0.75);

        return random_bytes($entropy);
    }
}
