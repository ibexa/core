<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Persistence\Token\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Token\Handler as HandlerInterface;
use Ibexa\Contracts\Core\Persistence\Token\Token;
use Ibexa\Contracts\Core\Persistence\Token\TokenType;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\TokenExpiredException;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\Token\Gateway as TokenGateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Gateway as TokenTypeGateway;

/**
 * @internal
 */
final class Handler implements HandlerInterface
{
    private Mapper $mapper;

    private TokenGateway $tokenGateway;

    private TokenTypeGateway $tokenTypeGateway;

    public function __construct(
        Mapper $mapper,
        TokenGateway $tokenGateway,
        TokenTypeGateway $tokenTypeGateway
    ) {
        $this->mapper = $mapper;
        $this->tokenGateway = $tokenGateway;
        $this->tokenTypeGateway = $tokenTypeGateway;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Core\Base\Exceptions\TokenExpiredException
     * @throws \Exception
     */
    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): Token {
        $persistenceTokenValue = $this->mapper->mapToken(
            $this->tokenGateway->getToken($tokenType, $token, $identifier)
        );

        if ($persistenceTokenValue->expires < time()) {
            throw new TokenExpiredException(
                $tokenType,
                $persistenceTokenValue->token,
                new DateTimeImmutable('@' . $persistenceTokenValue->expires)
            );
        }

        return $persistenceTokenValue;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function getTokenType(
        string $identifier
    ): TokenType {
        return $this->mapper->mapTokenType(
            $this->tokenTypeGateway->getTypeByIdentifier($identifier)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function createToken(CreateStruct $createStruct): Token
    {
        try {
            $typeId = $this->getTokenType($createStruct->type)->id;
        } catch (NotFoundException $exception) {
            $typeId = $this->tokenTypeGateway->insert($createStruct->type);
        }

        $tokenId = $this->tokenGateway->insert(
            $typeId,
            $createStruct->token,
            $createStruct->identifier,
            $createStruct->ttl
        );

        return $this->mapper->mapToken(
            $this->tokenGateway->getTokenById($tokenId)
        );
    }

    public function revokeTokenById(int $tokenId): void
    {
        $this->tokenGateway->revoke($tokenId);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function revokeTokenByIdentifier(string $tokenType, ?string $identifier): void
    {
        $type = $this->getTokenType($tokenType);
        $this->tokenGateway->revokeByIdentifier(
            $type->id,
            $identifier
        );
    }

    public function deleteToken(Token $token): void
    {
        $this->deleteTokenById($token->id);
    }

    public function deleteTokenById(int $tokenId): void
    {
        $this->tokenGateway->delete($tokenId);
    }

    public function deleteExpiredTokens(?string $tokenType = null): void
    {
        try {
            if (null !== $tokenType) {
                $typeId = $this->getTokenType($tokenType)->id;
            }
        } catch (NotFoundException $exception) {
            return;
        }

        $this->tokenGateway->deleteExpired($typeId ?? null);
    }
}
