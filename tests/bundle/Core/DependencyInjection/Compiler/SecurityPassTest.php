<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\SecurityPass;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Security\Authentication\AnonymousUserAccessListener;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class SecurityPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition('security.authentication.provider.rememberme', new Definition());
        $this->setDefinition('security.authentication.provider.guard', new Definition());
        $this->setDefinition('security.http_utils', new Definition());
        $this->setDefinition('security.authentication.success_handler', new Definition());
        $this->setDefinition(ConfigResolverInterface::class, new Definition());
        $this->setDefinition(SiteAccess::class, new Definition());
        $this->setDefinition(PermissionResolver::class, new Definition());
        $this->setDefinition(UserService::class, new Definition());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SecurityPass());
    }

    public function testAlteredHttpUtils(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'security.http_utils',
            'setSiteAccess',
            [new Reference(SiteAccess::class)]
        );
    }

    public function testAlteredSuccessHandler(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'security.authentication.success_handler',
            'setConfigResolver',
            [new Reference(ConfigResolverInterface::class)]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'security.authentication.success_handler',
            'setEventDispatcher',
            [new Reference('event_dispatcher')]
        );
    }

    public function testAnonymousUserAccessListenerWithFirewallLoginPaths(): void
    {
        $this->setDefinition(AnonymousUserAccessListener::class, new Definition());
        $this->setDefinition('event_dispatcher', new Definition());
        $this->setParameter('security.firewalls', ['main', 'admin']);

        // Setup form_login authenticators for both firewalls
        $mainFormLoginDef = new Definition();
        $mainFormLoginDef->setArguments([null, null, null, null, ['login_path' => '/login']]);
        $this->setDefinition('security.authenticator.form_login.main', $mainFormLoginDef);

        $adminFormLoginDef = new Definition();
        $adminFormLoginDef->setArguments([null, null, null, null, ['login_path' => '/admin/login']]);
        $this->setDefinition('security.authenticator.form_login.admin', $adminFormLoginDef);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            AnonymousUserAccessListener::class,
            '$firewallLoginPaths',
            [
                'main' => '/login',
                'admin' => '/admin/login',
            ]
        );
    }

    public function testAnonymousUserAccessListenerWithNoFormLogin(): void
    {
        $this->setDefinition(AnonymousUserAccessListener::class, new Definition());
        $this->setDefinition('event_dispatcher', new Definition());
        $this->setParameter('security.firewalls', ['api']);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            AnonymousUserAccessListener::class,
            '$firewallLoginPaths',
            []
        );
    }

    public function testSkipsWhenAnonymousUserAccessListenerNotDefined(): void
    {
        $this->setParameter('security.firewalls', ['main']);

        $this->compile();

        self::assertFalse($this->container->hasDefinition(AnonymousUserAccessListener::class));
    }
}
