<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\SecurityPass;
use Ibexa\Contracts\Core\Repository\Exceptions\PasswordInUnsupportedFormatException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Core\MVC\Symfony\Security\UserInterface as IbexaUserInterface;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;

class RepositoryAuthenticationProvider extends DaoAuthenticationProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var float|null */
    private $constantAuthTime;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    private $userService;

    public function setConstantAuthTime(float $constantAuthTime)
    {
        $this->constantAuthTime = $constantAuthTime;
    }

    public function setPermissionResolver(PermissionResolver $permissionResolver)
    {
        $this->permissionResolver = $permissionResolver;
    }

    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }

    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        if (!$user instanceof IbexaUserInterface) {
            parent::checkAuthentication($user, $token);

            return;
        }

        $apiUser = $user->getAPIUser();

        // $currentUser can either be an instance of UserInterface or just the username (e.g. during form login).
        /** @var \Ibexa\Core\MVC\Symfony\Security\UserInterface|string $currentUser */
        $currentUser = $token->getUser();
        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getAPIUser()->passwordHash !== $apiUser->passwordHash) {
                throw new BadCredentialsException('The credentials were changed in another session.');
            }

            $apiUser = $currentUser->getAPIUser();
        } else {
            $credentialsValid = $this->userService->checkUserCredentials($apiUser, $token->getCredentials());

            if (!$credentialsValid) {
                throw new BadCredentialsException('Invalid credentials', 0);
            }
        }

        // Finally inject current user in the Repository
        $this->permissionResolver->setCurrentUserReference($apiUser);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PasswordInUnsupportedFormatException
     */
    public function authenticate(TokenInterface $token)
    {
        $startTime = $this->startConstantTimer();

        try {
            $result = parent::authenticate($token);
        } catch (UnsupportedPasswordHashType $exception) {
            $this->sleepUsingConstantTimer($startTime);
            throw new PasswordInUnsupportedFormatException($exception);
        } catch (\Exception $e) {
            $this->sleepUsingConstantTimer($startTime);
            throw $e;
        }

        $this->sleepUsingConstantTimer($startTime);

        return $result;
    }

    private function startConstantTimer()
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
            usleep($remainingTime * 1000000);
        } elseif ($this->logger) {
            $this->logger->warning(
                sprintf(
                    'Authentication took longer than the configured constant time. Consider increasing the value of %s',
                    SecurityPass::CONSTANT_AUTH_TIME_SETTING
                ),
                [static::class]
            );
        }
    }
}

class_alias(RepositoryAuthenticationProvider::class, 'eZ\Publish\Core\MVC\Symfony\Security\Authentication\RepositoryAuthenticationProvider');
