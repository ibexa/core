<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\User;

use Ibexa\Core\MVC\Symfony\Security\User\BaseProvider;
use Ibexa\Core\MVC\Symfony\Security\User\EmailProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EmailProvider::class)]
final class EmailProviderTest extends BaseProviderTestCase
{
    protected function buildProvider(): BaseProvider
    {
        return new EmailProvider($this->userService, $this->permissionResolver);
    }

    protected function getUserIdentifier(): string
    {
        return 'foobar@example.org';
    }

    protected function getUserServiceMethod(): string
    {
        return 'loadUserByEmail';
    }
}
