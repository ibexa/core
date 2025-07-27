<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver\SiteAccessGroupConfigResolver;
use Ibexa\Bundle\Core\SiteAccess\Matcher;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;

class SiteAccessGroupConfigResolverTest extends ConfigResolverTestCase
{
    protected function getResolver(string $defaultNamespace = self::DEFAULT_NAMESPACE): ConfigResolverInterface
    {
        $staticSiteAccessProvider = new StaticSiteAccessProvider(
            [self::EXISTING_SA_NAME],
            [self::EXISTING_SA_NAME => [self::SA_GROUP]],
        );
        $siteAccess = new SiteAccess(
            self::EXISTING_SA_NAME,
            'default',
            $this->createMock(Matcher::class)
        );
        $configResolver = new SiteAccessGroupConfigResolver(
            $this->containerMock,
            $staticSiteAccessProvider,
            $defaultNamespace,
            []
        );
        $configResolver->setSiteAccess($siteAccess);

        return $configResolver;
    }

    protected function getScope(): string
    {
        return self::SA_GROUP;
    }
}
