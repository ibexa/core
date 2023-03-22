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
    private int $id;

    private string $type;

    private string $token;

    private ?string $identifier;

    private DateTimeImmutable $created;

    private DateTimeImmutable $expires;

    public function __construct(
        int $id,
        string $type,
        string $token,
        ?string $identifier,
        DateTimeImmutable $created,
        DateTimeImmutable $expires
    ) {
        parent::__construct();

        $this->id = $id;
        $this->type = $type;
        $this->token = $token;
        $this->identifier = $identifier;
        $this->created = $created;
        $this->expires = $expires;
    }

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

    public static function fromArray(array $properties): self
    {
        return new self(
            $properties['id'],
            $properties['type'],
            $properties['token'],
            $properties['identifier'],
            $properties['created'],
            $properties['expires'],
        );
    }
}
