<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Token;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;

final class GenerateTokenEvent extends AfterEvent
{
    private Token $token;

    private string $tokenType;

    private ?string $identifier;

    private int $ttl;

    private int $tokenLength;

    private ?TokenGeneratorInterface $tokenGenerator;

    public function __construct(
        Token $token,
        string $tokenType,
        int $ttl,
        ?string $identifier = null,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ) {
        $this->token = $token;
        $this->tokenType = $tokenType;
        $this->identifier = $identifier;
        $this->ttl = $ttl;
        $this->tokenLength = $tokenLength;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getTokenLength(): int
    {
        return $this->tokenLength;
    }

    public function getTokenGenerator(): ?TokenGeneratorInterface
    {
        return $this->tokenGenerator;
    }
}
