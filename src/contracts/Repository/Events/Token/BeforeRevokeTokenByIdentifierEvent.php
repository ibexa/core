<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Token;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;

final class BeforeRevokeTokenByIdentifierEvent extends AfterEvent
{
    private string $tokenType;

    private ?string $identifier;

    public function __construct(
        string $tokenType,
        ?string $identifier
    ) {
        $this->tokenType = $tokenType;
        $this->identifier = $identifier;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }
}
