<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Token;

use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;

final class WebSafeGenerator implements TokenGeneratorInterface
{
    private TokenGeneratorInterface $tokenGenerator;

    public function __construct(TokenGeneratorInterface $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @throws \Exception
     */
    public function generateToken(int $length = 64): string
    {
        $token = $this->tokenGenerator->generateToken($length);
        $encoded = base64_encode($token);

        return substr(
            rtrim(
                strtr($encoded, '+-', '/_'),
                '='
            ),
            0,
            $length
        );
    }
}
