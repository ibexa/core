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
            $this->getToken($tokenType, $token, $identifier);

            return true;
        } catch (NotFoundException|UnauthorizedException $exception) {
            return false;
        }
    }

    /**
     * @throws \Exception
     */
    public function generateToken(
        string $type,
        int $ttl,
        ?string $identifier = null,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ): Token {
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

    public function deleteToken(Token $token): void
    {
        $this->persistenceHandler->deleteTokenById($token->getId());
    }

    /**
     * @throws \Exception
     */
    private function buildDomainObject(
        PersistenceTokenValue $spiToken,
        PersistenceTokenTypeValue $spiTokenType
    ): Token {
        return Token::fromArray([
            'id' => $spiToken->id,
            'type' => $spiTokenType->identifier,
            'token' => $spiToken->token,
            'identifier' => $spiToken->identifier,
            'created' => new DateTimeImmutable('@' . $spiToken->created),
            'expires' => new DateTimeImmutable('@' . $spiToken->expires),
        ]);
    }
}
