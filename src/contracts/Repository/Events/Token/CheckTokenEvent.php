<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Token;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;

final class CheckTokenEvent extends AfterEvent
{
    private bool $result;

    private string $tokenType;

    private string $token;

    private ?string $identifier;

    public function __construct(
        bool $result,
        string $tokenType,
        string $token,
        ?string $identifier = null
    ) {
        $this->result = $result;
        $this->tokenType = $tokenType;
        $this->token = $token;
        $this->identifier = $identifier;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }
}
