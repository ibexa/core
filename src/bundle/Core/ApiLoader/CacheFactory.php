<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\ApiLoader;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CacheFactory.
 *
 * Service "ibexa.cache_pool", selects a Symfony cache service based on siteaccess[-group] setting "cache_service_name"
 */
class CacheFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param ConfigResolverInterface $configResolver
     *
     * @return TagAwareAdapterInterface
     */
    public function getCachePool(ConfigResolverInterface $configResolver)
    {
        /** @var AdapterInterface $cacheService */
        $cacheService = $this->container->get($configResolver->getParameter('cache_service_name'));

        // If cache service is already implementing TagAwareAdapterInterface, return as-is
        if ($cacheService instanceof TagAwareAdapterInterface) {
            return $cacheService;
        }

        return new TagAwareAdapter(
            $cacheService
        );
    }
}
