<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\Authentication\EventSubscriber;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\PasswordInUnsupportedFormatException;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Core\MVC\Symfony\Security\UserInterface as IbexaUserInterface;
use Ibexa\Core\Repository\User\Exception\PasswordHashTypeNotCompiled;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final class RepositoryUserAuthenticationSubscriber implements EventSubscriberInterface
{
    use LoggerAwareTrait;

    public const string CONSTANT_AUTH_TIME_SETTING = 'ibexa.security.authentication.constant_auth_time';

    private const int USLEEP_MULTIPLIER = 1000000;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserService $userService,
        private readonly float $constantAuthTime,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['validateRepositoryUser'],
        ];
    }

    public function validateRepositoryUser(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $passport = $event->getPassport();
        //validating password hash is not needed when SelfValidatingPassport is in use - this implementation is generally
        //used when there are no credentials to be checked (e.g. API token authentication).
        if ($passport instanceof SelfValidatingPassport) {
            return;
        }

        $badge = $passport->getBadge(UserBadge::class);
        if (!$badge instanceof UserBadge) {
            return;
        }

        $user = $badge->getUser();
        if (!$user instanceof IbexaUserInterface || !$user instanceof PasswordAuthenticatedUserInterface) {
            return;
        }

        $startTime = $this->startConstantTimer();
        try {
            $this->userService->checkUserCredentials(
                $user->getAPIUser(),
                $user->getPassword() ?? ''
            );
        } catch (UnsupportedPasswordHashType|PasswordHashTypeNotCompiled $exception) {
            $this->sleepUsingConstantTimer($startTime);

            throw new PasswordInUnsupportedFormatException($exception);
        } catch (Exception $e) {
            $this->sleepUsingConstantTimer($startTime);

            throw $e;
        }

        $this->sleepUsingConstantTimer($startTime);
    }

    private function startConstantTimer(): float
    {
        return microtime(true);
    }

    private function sleepUsingConstantTimer(float $startTime): void
    {
        if ($this->constantAuthTime <= 0.0) {
            return;
        }

        $remainingTime = $this->constantAuthTime - (microtime(true) - $startTime);
        if ($remainingTime > 0) {
            $microseconds = $remainingTime * self::USLEEP_MULTIPLIER;

            usleep((int)$microseconds);
        } elseif ($this->logger) {
            $this->logger->warning(
                sprintf(
                    'Authentication took longer than the configured constant time. Consider increasing the value of %s',
                    self::CONSTANT_AUTH_TIME_SETTING
                ),
                [__CLASS__]
            );
        }
    }
}
