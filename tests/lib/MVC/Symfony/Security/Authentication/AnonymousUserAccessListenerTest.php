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
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\Firewall\AccessListener;

final class AnonymousUserAccessListenerTest extends TestCase
{
    private MockObject&PermissionResolver $permissionResolver;

    private AccessListener $innerListener;

    private MockObject&Security $security;

    private AnonymousUserAccessListener $listener;

    private MockObject&AccessDecisionManagerInterface $accessDecisionManager;

    private MockObject&AccessMapInterface $accessMap;

    /** @var array<mixed, mixed> */
    private array $patterns = [null, null];

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionResolver = $this->createMock(PermissionResolver::class);
        $this->accessMap = $this->createMock(AccessMapInterface::class);
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);

        $token = new class() extends AbstractToken {
        };
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $this->innerListener = new AccessListener(
            $tokenStorage,
            $this->accessDecisionManager,
            $this->accessMap
        );

        // The default listener models a login-capable firewall (`main`).
        $this->security = $this->createSecurityForFirewall('main');
        $this->listener = new AnonymousUserAccessListener(
            $this->permissionResolver,
            $this->innerListener,
            $this->security,
            ['main' => '/login'],
            $this->accessMap
        );
    }

    public function testSupportsWithAnonymousUser(): void
    {
        $request = Request::create('/some/path');
        $this->mockAccessMapGetPatterns();

        self::assertTrue($this->listener->supports($request));
    }

    /**
     * A request carrying Basic-auth credentials must NOT switch off access_control:
     * the anonymous-login gate does not apply, but the decorated AccessListener
     * still enforces the protected pattern.
     */
    public function testAuthenticatedRequestStillEnforcesAccessControl(): void
    {
        $request = Request::create('http://testuser:password@example.com/some/path');
        $this->patterns = [['ROLE_USER'], null];
        $this->mockAccessMapGetPatterns();

        self::assertTrue($this->listener->supports($request));
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

        $this->listener->authenticate($event);
    }

    public function testSupportsSkipsUserContextHashRequest(): void
    {
        $request = Request::create('/_fos_user_context_hash');

        // The short-circuit must not even consult the access map.
        $this->accessMap
            ->expects(self::never())
            ->method('getPatterns');

        self::assertFalse($this->listener->supports($request));
    }

    /**
     * The login page is no longer hard-skipped; it defers to access_control, which
     * is a no-op when no rule protects it, so the login page stays reachable.
     *
     * @dataProvider provideLoginPaths
     */
    public function testSupportsDefersToAccessControlOnLoginPage(string $path): void
    {
        $request = Request::create($path);
        $this->patterns = [null, null];
        $this->mockAccessMapGetPatterns();

        self::assertNull($this->listener->supports($request));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLoginPaths(): iterable
    {
        yield 'simple login' => ['/login'];
        yield 'admin login' => ['/admin/login'];
        yield 'login with query string' => ['/login?redirect=/content'];
    }

    /**
     * @dataProvider providedSupportedPaths
     */
    public function testSupportsReturnsTrueForSupportedPaths(string $path): void
    {
        $request = Request::create($path);
        $this->mockAccessMapGetPatterns();

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

    /**
     * Core regression: a firewall without a `login_path` (e.g. `ibexa_rest`) must
     * still enforce access_control instead of the decorator silently skipping it.
     */
    public function testSupportsEnforcesAccessControlOnFirewallWithoutLoginPath(): void
    {
        $listener = $this->createListenerForFirewall('ibexa_rest', ['main' => '/login']);
        $request = Request::create('/api/ibexa/v2/content/objects/1');
        $this->patterns = [['ROLE_USER'], null];
        $this->mockAccessMapGetPatterns();

        // Defers to the decorated AccessListener, which wants to run because the
        // path is protected.
        self::assertTrue($listener->supports($request));
    }

    /**
     * The transparent fallback must not over-block: a public path on a firewall
     * without a `login_path` still passes through when no rule protects it.
     */
    public function testSupportsDoesNotBlockPublicPathOnFirewallWithoutLoginPath(): void
    {
        $listener = $this->createListenerForFirewall('ibexa_rest', ['main' => '/login']);
        $request = Request::create('/api/ibexa/v2/content/objects/1');
        $this->patterns = [null, null];
        $this->mockAccessMapGetPatterns();

        self::assertNull($listener->supports($request));
    }

    /**
     * On a firewall without a `login_path` the anonymous-login gate must not run,
     * and a protected path must be rejected by the decorated AccessListener.
     */
    public function testAuthenticateEnforcesAccessControlOnFirewallWithoutLoginPath(): void
    {
        $listener = $this->createListenerForFirewall('ibexa_rest', ['main' => '/login']);
        $request = Request::create('/api/ibexa/v2/content/objects/1');
        $this->patterns = [['ROLE_USER'], null];
        $this->mockAccessMapGetPatterns();

        $this->permissionResolver
            ->expects(self::never())
            ->method('canUser');

        // Prime `_access_control_attributes` via the transparent supports() path.
        $listener->supports($request);

        $this->accessDecisionManager
            ->expects(self::once())
            ->method('decide')
            ->willReturn(false);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->expectException(AccessDeniedException::class);

        $listener->authenticate($event);
    }

    public function testHandleWhenTheAccessDecisionManagerDecidesToRefuseAccess(): void
    {
        $this->patterns = [['foo' => 'bar'], null];
        $event = $this->prepareForAccessListenerTests();

        $this->expectException(AccessDeniedException::class);

        $this->listener->authenticate($event);
    }

    public function testHandleWhenPublicAccessIsAllowed(): void
    {
        $this->patterns = [[AuthenticatedVoter::PUBLIC_ACCESS], null];
        $event = $this->prepareForAccessListenerTests();

        $this->accessDecisionManager->expects(self::once())
            ->method('decide')
            ->willReturn(true);

        $this->listener->authenticate($event);
    }

    public function testHandleWhenAccessMapReturnsEmptyAttributes(): void
    {
        $this->patterns = [[], null];
        $event = $this->prepareForAccessListenerTests();

        $this->listener->authenticate($event);
    }

    private function prepareForAccessListenerTests(): RequestEvent
    {
        $siteAccess = new SiteAccess('admin', 'default');
        $request = new Request([], [], ['siteaccess' => $siteAccess]);

        $this->permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with('user', 'login', $siteAccess)
            ->willReturn(true);

        $this->mockAccessMapGetPatterns();

        $this->listener->supports($request);

        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    private function mockAccessMapGetPatterns(): void
    {
        $this->accessMap
            ->expects(self::once())
            ->method('getPatterns')
            ->willReturnCallback(function (): array {
                return $this->patterns;
            });
    }

    /**
     * @param array<string, string> $firewallLoginPaths
     */
    private function createListenerForFirewall(
        string $firewallName,
        array $firewallLoginPaths
    ): AnonymousUserAccessListener {
        return new AnonymousUserAccessListener(
            $this->permissionResolver,
            $this->innerListener,
            $this->createSecurityForFirewall($firewallName),
            $firewallLoginPaths,
            $this->accessMap
        );
    }

    private function createSecurityForFirewall(string $firewallName): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('getFirewallConfig')
            ->willReturn(new FirewallConfig(
                name: $firewallName,
                userChecker: 'security.user_checker',
                requestMatcher: null,
                securityEnabled: true,
            ));

        return $security;
    }
}
