<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Event;

use Ibexa\Contracts\Core\Repository\Decorator\TokenServiceDecorator;
use Ibexa\Contracts\Core\Repository\Events\Token\BeforeCheckTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\BeforeDeleteTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\BeforeGenerateTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\BeforeGetTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\CheckTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\DeleteTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\GenerateTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\Token\GetTokenEvent;
use Ibexa\Contracts\Core\Repository\TokenService as TokenServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TokenService extends TokenServiceDecorator
{
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TokenServiceInterface $innerService,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($innerService);

        $this->eventDispatcher = $eventDispatcher;
    }

    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): Token {
        $eventData = [$tokenType, $token, $identifier];

        $beforeEvent = new BeforeGetTokenEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getResult();
        }

        $result = $beforeEvent->hasResult()
            ? $beforeEvent->getToken()
            : $this->innerService->getToken(...$eventData);

        $this->eventDispatcher->dispatch(
            new GetTokenEvent($result, ...$eventData)
        );

        return $result;
    }

    public function checkToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): bool {
        $eventData = [$tokenType, $token, $identifier];

        $beforeEvent = new BeforeCheckTokenEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getResult();
        }

        $result = $beforeEvent->hasResult()
            ? $beforeEvent->getResult()
            : $this->innerService->checkToken(...$eventData);

        $this->eventDispatcher->dispatch(
            new CheckTokenEvent($result, ...$eventData)
        );

        return $result;
    }

    public function generateToken(
        string $type,
        ?string $identifier,
        int $ttl,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ): Token {
        $eventData = [$type, $identifier, $ttl, $tokenLength, $tokenGenerator];

        $beforeEvent = new BeforeGenerateTokenEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getToken();
        }

        $token = $beforeEvent->hasToken()
            ? $beforeEvent->getToken()
            : $this->innerService->generateToken(...$eventData);

        $this->eventDispatcher->dispatch(
            new GenerateTokenEvent($token, ...$eventData)
        );

        return $token;
    }

    public function deleteToken(Token $token): void
    {
        $eventData = [$token];

        $beforeEvent = new BeforeDeleteTokenEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->deleteToken($token);

        $this->eventDispatcher->dispatch(
            new DeleteTokenEvent(...$eventData)
        );
    }
}
