<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Token;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;
use UnexpectedValueException;

final class BeforeGenerateTokenEvent extends BeforeEvent
{
    private ?Token $token = null;

    private string $tokenType;

    private ?string $identifier;

    private int $ttl;

    private int $tokenLength;

    private ?TokenGeneratorInterface $tokenGenerator;

    public function __construct(
        string $type,
        int $ttl,
        ?string $identifier = null,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ) {
        $this->tokenType = $type;
        $this->ttl = $ttl;
        $this->identifier = $identifier;
        $this->tokenLength = $tokenLength;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function getToken(): Token
    {
        if (!$this->hasToken()) {
            throw new UnexpectedValueException(
                'Return value is not set.' . PHP_EOL
                . 'Check hasToken() or set it using setToken() before you call the getter.',
            );
        }

        return $this->token;
    }

    public function setToken(?Token $token): void
    {
        $this->token = $token;
    }

    public function hasToken(): bool
    {
        return $this->token instanceof Token;
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
