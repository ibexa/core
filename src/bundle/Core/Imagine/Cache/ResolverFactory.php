<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Cache;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;

class ResolverFactory
{
    private ConfigResolverInterface $configResolver;

    private ResolverInterface $resolver;

    /** @phpstan-var class-string<\Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface> */
    private string $proxyResolverClass;

    /** @phpstan-var class-string<\Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface> */
    private string $relativeResolverClass;

    /**
     * @phpstan-param class-string<\Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface> $proxyResolverClass
     * @phpstan-param class-string<\Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface> $relativeResolverClass
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        ResolverInterface $resolver,
        string $proxyResolverClass,
        string $relativeResolverClass
    ) {
        $this->configResolver = $configResolver;
        $this->resolver = $resolver;
        $this->proxyResolverClass = $proxyResolverClass;
        $this->relativeResolverClass = $relativeResolverClass;
    }

    /**
     * @return \Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface
     */
    public function createCacheResolver(): ResolverInterface
    {
        $imageHost = $this->configResolver->hasParameter('image_host') ?
            $this->configResolver->getParameter('image_host') :
            '';

        if ($imageHost === '') {
            return $this->resolver;
        }

        if ($imageHost === '/') {
            $resolverDecoratorClass = $this->relativeResolverClass;
        } else {
            $resolverDecoratorClass = $this->proxyResolverClass;
        }

        return new $resolverDecoratorClass($this->resolver, [$imageHost]);
    }
}
