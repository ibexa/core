<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Persistence\Token\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Token\Handler;
use Ibexa\Contracts\Core\Persistence\Token\Token as PersistenceTokenValue;
use Ibexa\Contracts\Core\Persistence\Token\TokenType as PersistenceTokenTypeValue;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\TokenService as TokenServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;
use Ibexa\Core\Base\Exceptions\TokenLengthException;

final class TokenService implements TokenServiceInterface
{
    private Handler $persistenceHandler;

    private TokenGeneratorInterface $defaultTokenGenerator;

    public function __construct(
        Handler $persistenceHandler,
        TokenGeneratorInterface $defaultTokenGenerator
    ) {
        $this->persistenceHandler = $persistenceHandler;
        $this->defaultTokenGenerator = $defaultTokenGenerator;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Exception
     */
    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): Token {
        $type = $this->persistenceHandler->getTokenType($tokenType);

        return $this->buildDomainObject(
            $this->persistenceHandler->getToken(
                $type->identifier,
                $token,
                $identifier
            ),
            $type
        );
    }

    /**
     * @throws \Exception
     */
    public function checkToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): bool {
        try {
            $token = $this->getToken($tokenType, $token, $identifier);

            return !$token->isRevoked();
        } catch (NotFoundException|UnauthorizedException $exception) {
            return false;
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    public function generateToken(
        string $type,
        int $ttl,
        ?string $identifier = null,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ): Token {
        if ($tokenLength > Token::MAX_LENGTH) {
            throw new TokenLengthException($tokenLength);
        }

        $createStruct = new CreateStruct([
            'type' => $type,
            'token' => ($tokenGenerator ?? $this->defaultTokenGenerator)->generateToken($tokenLength),
            'identifier' => $identifier,
            'ttl' => $ttl,
        ]);

        $token = $this->persistenceHandler->createToken($createStruct);
        $tokenType = $this->persistenceHandler->getTokenType($type);

        return $this->buildDomainObject(
            $token,
            $tokenType
        );
    }

    public function revokeToken(Token $token): void
    {
        $this->persistenceHandler->revokeTokenById($token->getId());
    }

    public function revokeTokenByIdentifier(string $tokenType, ?string $identifier): void
    {
        $this->persistenceHandler->revokeTokenByIdentifier($tokenType, $identifier);
    }

    public function deleteToken(Token $token): void
    {
        $this->persistenceHandler->deleteTokenById($token->getId());
    }

    /**
     * @throws \Exception
     */
    private function buildDomainObject(
        PersistenceTokenValue $token,
        PersistenceTokenTypeValue $tokenType
    ): Token {
        return new Token(
            $token->id,
            $tokenType->identifier,
            $token->token,
            $token->identifier,
            new DateTimeImmutable('@' . $token->created),
            new DateTimeImmutable('@' . $token->expires),
            $token->revoked
        );
    }
}
