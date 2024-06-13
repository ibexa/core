<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Security;

use Ibexa\Core\MVC\Symfony\Security\InteractiveLoginToken;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use PHPUnit\Framework\TestCase;

class InteractiveLoginTokenTest extends TestCase
{
    public function testConstruct()
    {
        $user = $this->createMock(UserInterface::class);
        $originalTokenType = 'FooBar';
        $credentials = 'my_credentials';
        $providerKey = 'key';
        $roles = ['ROLE_USER', 'ROLE_TEST', 'ROLE_FOO'];
        $expectedRoles = [];
        foreach ($roles as $role) {
            if (is_string($role)) {
                $expectedRoles[] = $role;
            } else {
                $expectedRoles[] = $role;
            }
        }

        $token = new InteractiveLoginToken($user, $originalTokenType, $credentials, $providerKey, $roles);
        self::assertSame($user, $token->getUser());
        self::assertTrue($token->isAuthenticated());
        self::assertSame($originalTokenType, $token->getOriginalTokenType());
        self::assertSame($credentials, $token->getCredentials());
        self::assertSame($providerKey, $token->getFirewallName());
        self::assertEquals($expectedRoles, $token->getRoleNames());
    }

    public function testSerialize()
    {
        $user = $this->createMock(UserInterface::class);
        $originalTokenType = 'FooBar';
        $credentials = 'my_credentials';
        $providerKey = 'key';
        $roles = ['ROLE_USER', 'ROLE_TEST', 'ROLE_FOO'];

        $token = new InteractiveLoginToken($user, $originalTokenType, $credentials, $providerKey, $roles);
        $serialized = serialize($token);
        $unserializedToken = unserialize($serialized);
        self::assertEquals($token, $unserializedToken);
    }
}
