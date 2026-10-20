<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\UserExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Test\IntegrationTestCase;

final class UserExtensionTest extends IntegrationTestCase
{
    /** @var PermissionResolver&MockObject */
    private PermissionResolver $permissionResolver;

    /** @var UserService&MockObject */
    private UserService $userService;

    /** @var array<int, User> */
    private array $users = [];

    private int $currentUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = $this->createMock(UserService::class);
        $this->userService
            ->method('loadUser')
            ->willReturnCallback(fn (int $id): User => $this->users[$id]);

        $this->permissionResolver = $this->createMock(PermissionResolver::class);
        $this->permissionResolver
            ->method('getCurrentUserReference')
            ->willReturnCallback(function (): UserReference {
                $reference = $this->createMock(UserReference::class);
                $reference->method('getUserId')->willReturn($this->currentUserId);

                return $reference;
            });

        $this->getUser(10, true);
    }

    protected function getExtensions(): array
    {
        return [
            new UserExtension(
                $this->userService,
                $this->permissionResolver
            ),
        ];
    }

    public function getUser(
        int $id,
        bool $isCurrent = false
    ): User {
        if (!isset($this->users[$id])) {
            $user = $this->createMock(User::class);
            $user->method('getUserId')->willReturn($id);

            $this->users[$id] = $user;

            if ($isCurrent) {
                $this->currentUserId = $id;
            }
        }

        return $this->users[$id];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/_fixtures/user_functions';
    }
}
