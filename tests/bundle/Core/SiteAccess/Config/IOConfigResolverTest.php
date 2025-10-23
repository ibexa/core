<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\SiteAccess\Config;

use Ibexa\Bundle\Core\SiteAccess\Config\ComplexConfigProcessor;
use Ibexa\Bundle\Core\SiteAccess\Config\IOConfigResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\SiteAccess\Config\IOConfigResolver
 */
class IOConfigResolverTest extends TestCase
{
    private const DEFAULT_NAMESPACE = 'ibexa.site_access.config';

    /** @var ConfigResolverInterface|MockObject */
    private $configResolver;

    /** @var SiteAccessService|MockObject */
    private $siteAccessService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->siteAccessService = $this->createMock(SiteAccessService::class);
    }

    public function testGetUrlPrefix(): void
    {
        $this->siteAccessService
            ->method('getCurrent')
            ->willReturn(new SiteAccess('demo_site'));

        $this->configResolver
            ->method('hasParameter')
            ->with('io.url_prefix', null, 'demo_site')
            ->willReturn(true);
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['io.url_prefix', null, 'demo_site', '$var_dir$/demo_site/$storage_dir$'],
                ['var_dir', self::DEFAULT_NAMESPACE, 'demo_site', 'var'],
                ['storage_dir', self::DEFAULT_NAMESPACE, 'demo_site', 'storage'],
            ]);

        $complexConfigProcessor = new ComplexConfigProcessor(
            $this->configResolver,
            $this->siteAccessService
        );

        $ioConfigResolver = new IOConfigResolver(
            $complexConfigProcessor
        );

        self::assertEquals('var/demo_site/storage', $ioConfigResolver->getUrlPrefix());
    }

    public function testGetLegacyUrlPrefix(): void
    {
        $this->siteAccessService
            ->method('getCurrent')
            ->willReturn(new SiteAccess('demo_site'));

        $this->configResolver
            ->method('hasParameter')
            ->with('io.legacy_url_prefix', null, 'demo_site')
            ->willReturn(true);
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['io.legacy_url_prefix', null, 'demo_site', '$var_dir$/demo_site/$storage_dir$'],
                ['var_dir', self::DEFAULT_NAMESPACE, 'demo_site', 'var'],
                ['storage_dir', self::DEFAULT_NAMESPACE, 'demo_site', 'legacy_storage'],
            ]);

        $complexConfigProcessor = new ComplexConfigProcessor(
            $this->configResolver,
            $this->siteAccessService
        );

        $ioConfigResolver = new IOConfigResolver(
            $complexConfigProcessor
        );

        self::assertEquals('var/demo_site/legacy_storage', $ioConfigResolver->getLegacyUrlPrefix());
    }

    public function testGetRootDir(): void
    {
        $this->siteAccessService
            ->method('getCurrent')
            ->willReturn(new SiteAccess('demo_site'));

        $this->configResolver
            ->method('hasParameter')
            ->with('io.root_dir', null, 'demo_site')
            ->willReturn(true);
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['io.root_dir', null, 'demo_site', '/path/to/ibexa/web/$var_dir$/demo_site/$storage_dir$'],
                ['var_dir', self::DEFAULT_NAMESPACE, 'demo_site', 'var'],
                ['storage_dir', self::DEFAULT_NAMESPACE, 'demo_site', 'legacy_storage'],
            ]);

        $complexConfigProcessor = new ComplexConfigProcessor(
            $this->configResolver,
            $this->siteAccessService
        );

        $ioConfigResolver = new IOConfigResolver(
            $complexConfigProcessor
        );

        self::assertEquals('/path/to/ibexa/web/var/demo_site/legacy_storage', $ioConfigResolver->getRootDir());
    }
}
