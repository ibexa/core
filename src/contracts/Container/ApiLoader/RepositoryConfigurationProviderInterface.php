<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Container\ApiLoader;

/**
 * @phpstan-type TRepositoryStorageConfiguration array{engine: string, connection: string, config?: array<string, mixed>}
 * @phpstan-type TRepositorySearchConfiguration array{engine: string, connection: string}
 * @phpstan-type TRepositoryConfiguration array{
 *     alias: string,
 *     storage: TRepositoryStorageConfiguration,
 *     search: TRepositorySearchConfiguration,
 *     fields_groups: array{default: string, list: string[]},
 *     options: array<string, mixed>
 * }
 */
interface RepositoryConfigurationProviderInterface
{
    /**
     * @phpstan-return TRepositoryConfiguration
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getRepositoryConfig(): array;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getCurrentRepositoryAlias(): string;

    public function getDefaultRepositoryAlias(): ?string;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getStorageConnectionName(): string;
}
