<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Token;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use UnexpectedValueException;

final class BeforeGetTokenEvent extends BeforeEvent
{
    private ?Token $result = null;

    private string $tokenType;

    private string $token;

    private ?string $identifier;

    public function __construct(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ) {
        $this->tokenType = $tokenType;
        $this->token = $token;
        $this->identifier = $identifier;
    }

    public function getResult(): Token
    {
        if (!$this->hasResult()) {
            throw new UnexpectedValueException(
                'Return value is not set.' . PHP_EOL
                . 'Check hasResult() or set it using setResult() before you call the getter.'
            );
        }

        return $this->result;
    }

    public function setResult(?Token $result): void
    {
        $this->result = $result;
    }

    public function hasResult(): bool
    {
        return $this->result instanceof Token;
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
