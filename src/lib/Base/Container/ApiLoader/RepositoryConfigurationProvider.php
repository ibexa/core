<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Container\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidRepositoryException;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

/**
 * @phpstan-import-type TRepositoryConfiguration from RepositoryConfigurationProviderInterface
 * @phpstan-import-type TRepositoryStorageConfiguration from RepositoryConfigurationProviderInterface
 * @phpstan-import-type TRepositorySearchConfiguration from RepositoryConfigurationProviderInterface
 *
 * @phpstan-type TRepositoryListItemConfiguration array{
 *      storage: TRepositoryStorageConfiguration,
 *      search: TRepositorySearchConfiguration,
 *      fields_groups: array{default: string, list: string[]},
 *      options: array<string, mixed>
 * }
 * @phpstan-type TRepositoryListConfiguration array<string, TRepositoryListItemConfiguration>
 */
final readonly class RepositoryConfigurationProvider implements RepositoryConfigurationProviderInterface
{
    private const string REPOSITORY_STORAGE = 'storage';
    private const string REPOSITORY_CONNECTION = 'connection';
    private const string DEFAULT_CONNECTION_NAME = 'default';

    /**
     * @phpstan-param TRepositoryListConfiguration $repositories
     */
    public function __construct(
        private ConfigResolverInterface $configResolver,
        private array $repositories,
    ) {
    }

    public function getRepositoryConfig(): array
    {
        // Takes configured repository as the reference, if it exists.
        // If not, the first configured repository is considered instead.
        /** @var string|null $repositoryAlias */
        $repositoryAlias = $this->configResolver->getParameter('repository');
        $repositoryAlias = $repositoryAlias ?? $this->getDefaultRepositoryAlias();

        if (empty($repositoryAlias) || !isset($this->repositories[$repositoryAlias])) {
            throw new InvalidRepositoryException(
                "Undefined Repository '$repositoryAlias'. Check if the Repository is configured in your project's ibexa.yaml."
            );
        }

        return ['alias' => $repositoryAlias] + $this->repositories[$repositoryAlias];
    }

    public function getCurrentRepositoryAlias(): string
    {
        return $this->getRepositoryConfig()['alias'];
    }

    public function getDefaultRepositoryAlias(): ?string
    {
        $aliases = array_keys($this->repositories);

        return array_shift($aliases);
    }

    public function getStorageConnectionName(): string
    {
        $repositoryConfig = $this->getRepositoryConfig();

        return $repositoryConfig[self::REPOSITORY_STORAGE][self::REPOSITORY_CONNECTION] ?? self::DEFAULT_CONNECTION_NAME;
    }
}
