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
        $this->setDefinition('ibexa.config.resolver', new Definition());
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
}
