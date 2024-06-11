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
final class RepositoryConfigurationProvider implements RepositoryConfigurationProviderInterface
{
    private RepositoryConfigurationProviderInterface $inner;

    public function __construct(RepositoryConfigurationProviderInterface $configurationProvider)
    {
        $this->inner = $configurationProvider;
    }

    public function getRepositoryConfig(): array
    {
        return $this->inner->getRepositoryConfig();
    }

    public function getCurrentRepositoryAlias(): string
    {
        return $this->inner->getCurrentRepositoryAlias();
    }

    public function getDefaultRepositoryAlias(): ?string
    {
        return $this->inner->getDefaultRepositoryAlias();
    }

    public function getStorageConnectionName(): string
    {
        return $this->inner->getStorageConnectionName();
    }
}
