<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Token;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

final class Token extends ValueObject
{
    protected int $id;

    protected string $type;

    protected string $token;

    protected ?string $identifier = null;

    protected DateTimeImmutable $created;

    protected DateTimeImmutable $expires;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getExpires(): DateTimeImmutable
    {
        return $this->expires;
    }

    public function __toString(): string
    {
        return $this->token;
    }
}
