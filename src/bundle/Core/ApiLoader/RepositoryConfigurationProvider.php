<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\ApiLoader;

use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;

/**
 * @deprecated 5.0.0 The "\Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider" class is deprecated, will be removed in 6.0.0.
 * Inject {@see \Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface} from Dependency Injection Container instead.
 */
final readonly class RepositoryConfigurationProvider implements RepositoryConfigurationProviderInterface
{
    public function __construct(private RepositoryConfigurationProviderInterface $configurationProvider)
    {
    }

    public function getRepositoryConfig(): array
    {
        return $this->configurationProvider->getRepositoryConfig();
    }

    public function getCurrentRepositoryAlias(): string
    {
        return $this->configurationProvider->getCurrentRepositoryAlias();
    }

    public function getDefaultRepositoryAlias(): ?string
    {
        return $this->configurationProvider->getDefaultRepositoryAlias();
    }

    public function getStorageConnectionName(): string
    {
        return $this->configurationProvider->getStorageConnectionName();
    }
}
