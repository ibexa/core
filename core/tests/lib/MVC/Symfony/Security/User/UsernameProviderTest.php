<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\User;

use Ibexa\Core\MVC\Symfony\Security\User\BaseProvider;
use Ibexa\Core\MVC\Symfony\Security\User\UsernameProvider;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Security\User\UsernameProvider
 */
final class UsernameProviderTest extends BaseProviderTestCase
{
    protected function buildProvider(): BaseProvider
    {
        return new UsernameProvider($this->userService, $this->permissionResolver);
    }

    protected function getUserIdentifier(): string
    {
        return 'foobar';
    }

    protected function getUserServiceMethod(): string
    {
        return 'loadUserByLogin';
    }
}
