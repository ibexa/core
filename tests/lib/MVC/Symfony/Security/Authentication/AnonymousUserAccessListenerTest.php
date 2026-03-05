<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\MVC\Symfony\Security\Authentication\AnonymousUserAccessListener;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Firewall\AccessListener;

final class AnonymousUserAccessListenerTest extends TestCase
{
    private MockObject&PermissionResolver $permissionResolver;

    private MockObject&AccessListener $innerListener;

    private MockObject&Security $security;

    private AnonymousUserAccessListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionResolver = $this->createMock(PermissionResolver::class);
        $this->innerListener = $this->createMock(AccessListener::class);
        $this->security = $this->createMock(Security::class);

        // Configure firewall config mock to return 'main' firewall
        $firewallConfig = new FirewallConfig(
            name: 'main',
            userChecker: 'security.user_checker',
            requestMatcher: null,
            securityEnabled: true,
        );

        $this->security
            ->method('getFirewallConfig')
            ->willReturn($firewallConfig);

        $this->listener = new AnonymousUserAccessListener(
            $this->permissionResolver,
            $this->innerListener,
            $this->security,
            ['main' => '/login'],
        );
    }

    public function testSupportsWithAnonymousUser(): void
    {
        $request = Request::create('/some/path');

        self::assertTrue($this->listener->supports($request));
    }

    public function testSupportsWithAuthenticatedUser(): void
    {
        $request = Request::create('http://testuser:password@example.com/some/path');

        self::assertFalse($this->listener->supports($request));
    }

    public function testAuthenticateWhenUserCanLogin(): void
    {
        $siteAccess = new SiteAccess('admin', 'default');
        $request = new Request([], [], ['siteaccess' => $siteAccess]);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with('user', 'login', $siteAccess)
            ->willReturn(true);

        $this->innerListener
            ->expects(self::once())
            ->method('authenticate')
            ->with($event);

        $this->listener->authenticate($event);
    }

    public function testAuthenticateWhenUserCannotLogin(): void
    {
        $siteAccess = new SiteAccess('site', 'default');
        $request = new Request([], [], ['siteaccess' => $siteAccess]);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with('user', 'login', $siteAccess)
            ->willReturn(false);

        $this->innerListener
            ->expects(self::never())
            ->method('authenticate');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Anonymous user cannot login to the current siteaccess');

        $this->listener->authenticate($event);
    }

    public function testAuthenticateSkipsSubRequest(): void
    {
        $siteAccess = new SiteAccess('admin', 'default');
        $request = new Request([], [], ['siteaccess' => $siteAccess]);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->permissionResolver
            ->expects(self::never())
            ->method('canUser');

        $this->innerListener
            ->expects(self::never())
            ->method('authenticate');

        $this->listener->authenticate($event);
    }

    /**
     * @dataProvider provideUnsupportedPaths
     */
    public function testSupportsReturnsFalseForUnsupportedPaths(string $path): void
    {
        $request = Request::create($path);

        self::assertFalse($this->listener->supports($request));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnsupportedPaths(): iterable
    {
        yield 'simple login' => ['/login'];
        yield 'admin login' => ['/admin/login'];
        yield 'login with query string' => ['/login?redirect=/content'];
        yield 'user context hash' => ['/_fos_user_context_hash'];
    }

    /**
     * @dataProvider providedSupportedPaths
     */
    public function testSupportsReturnsTrueForSupportedPaths(string $path): void
    {
        $request = Request::create($path);

        self::assertTrue($this->listener->supports($request));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providedSupportedPaths(): iterable
    {
        yield 'home' => ['/'];
        yield 'content view' => ['/content/view/full/2'];
        yield 'api endpoint' => ['/api/ibexa/v2/content/objects/1'];
        yield 'hacky url' => ["/content/view/full/2?myRandomHackyGetParam='login'"];
        yield 'login check' => ['/login_check'];
        yield 'login as a beginning of content name' => ['/login-as-part-of-content-name'];
        yield 'login as a part of content name' => ['/as-part-of-login-content-name'];
        yield 'login as an ending of content name' => ['/as-part-of-content-name-login'];
    }
}
