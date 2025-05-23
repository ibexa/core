<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver\DefaultScopeConfigResolver;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver\GlobalScopeConfigResolver;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver\SiteAccessGroupConfigResolver;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver\StaticSiteAccessConfigResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

abstract class AbstractParserTestCase extends AbstractExtensionTestCase
{
    protected const EMPTY_SA_GROUP = 'empty_group';

    protected function setUp(): void
    {
        parent::setUp();

        $loader = new YamlFileLoader(
            $this->container,
            new FileLocator(__DIR__ . '/../../Fixtures')
        );

        $loader->load('parameters.yml');
    }

    /**
     * Asserts a parameter from ConfigResolver has expected value for given scope.
     *
     * @param string $parameterName
     * @param mixed $expectedValue
     * @param string $scope SiteAccess name, group, default or global
     * @param bool $assertSame Set to false if you want to use assertEquals() instead of assertSame()
     */
    protected function assertConfigResolverParameterValue($parameterName, $expectedValue, $scope, $assertSame = true)
    {
        $chainConfigResolver = $this->getConfigResolver();
        $assertMethod = $assertSame ? 'assertSame' : 'assertEquals';
        $this->$assertMethod($expectedValue, $chainConfigResolver->getParameter($parameterName, 'ibexa.site_access.config', $scope));
    }

    protected function getConfigResolver(): ConfigResolverInterface
    {
        $chainConfigResolver = new ChainConfigResolver();
        $siteAccessProvider = $this->getSiteAccessProviderMock();

        $configResolvers = [
            new DefaultScopeConfigResolver($this->container, 'default'),
            new SiteAccessGroupConfigResolver($this->container, $siteAccessProvider, 'default', [self::EMPTY_SA_GROUP => []]),
            new StaticSiteAccessConfigResolver($this->container, $siteAccessProvider, 'default'),
            new GlobalScopeConfigResolver($this->container, 'default'),
        ];

        foreach ($configResolvers as $priority => $configResolver) {
            $chainConfigResolver->addResolver($configResolver, $priority);
        }

        return $chainConfigResolver;
    }

    protected function getSiteAccessProviderMock(): SiteAccessProviderInterface
    {
        $siteAccessProvider = $this->createMock(SiteAccessProviderInterface::class);
        $siteAccessProvider
            ->method('isDefined')
            ->willReturnMap([
                ['ibexa_demo_site', true],
                ['fre', true],
                ['fre2', true],
                ['ibexa_demo_site_admin', true],
                ['empty_group', false],
            ]);
        $siteAccessProvider
            ->method('getSiteAccess')
            ->willReturnMap([
                ['ibexa_demo_site', $this->getSiteAccess('ibexa_demo_site', StaticSiteAccessProvider::class, ['ibexa_demo_group', 'ibexa_demo_frontend_group'])],
                ['fre', $this->getSiteAccess('fre', StaticSiteAccessProvider::class, ['ibexa_demo_group', 'ibexa_demo_frontend_group'])],
                ['fre2', $this->getSiteAccess('fre', StaticSiteAccessProvider::class, ['ibexa_demo_group', 'ibexa_demo_frontend_group'])],
                ['ibexa_demo_site_admin', $this->getSiteAccess('ibexa_demo_site_admin', StaticSiteAccessProvider::class, ['ibexa_demo_group'])],
            ]);

        return $siteAccessProvider;
    }

    /**
     * @param string[] $groupNames
     */
    protected function getSiteAccess(string $name, string $provider, array $groupNames): SiteAccess
    {
        $siteAccess = new SiteAccess($name, SiteAccess::DEFAULT_MATCHING_TYPE, null, $provider);
        $siteAccessGroups = [];
        foreach ($groupNames as $groupName) {
            $siteAccessGroups[] = new SiteAccessGroup($groupName);
        }
        $siteAccess->groups = $siteAccessGroups;

        return $siteAccess;
    }
}
