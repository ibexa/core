<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type TRepositoryListItemConfiguration from \Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider
 */
abstract class BaseRepositoryConfigurationProviderTestCase extends TestCase
{
    protected const string MAIN_REPOSITORY_ALIAS = 'main';
    protected const array MAIN_REPOSITORY_CONFIG = [
        'storage' => ['engine' => 'foo', 'connection' => 'some_connection'],
        'search' => ['engine' => 'foo_search', 'connection' => 'some_connection'],
        'fields_groups' => ['default' => 'meta', 'list' => ['content', 'meta']],
        'options' => [],
    ];
    protected const array REPOSITORIES_CONFIG = [
        self::MAIN_REPOSITORY_ALIAS => self::MAIN_REPOSITORY_CONFIG,
        'another' => [
            'storage' => ['engine' => 'bar', 'connection' => 'bar_connection'],
            'search' => ['engine' => 'bar_search', 'connection' => 'bar_search_connection'],
            'fields_groups' => ['default' => 'content', 'list' => ['meta', 'content']],
            'options' => [],
        ],
    ];

    /**
     * @phpstan-return TRepositoryListItemConfiguration
     */
    protected function buildNormalizedSingleRepositoryConfig(
        string $storageEngine,
        string $storageConnection = 'default_connection'
    ): array {
        return [
            'storage' => [
                'engine' => $storageEngine,
                'connection' => $storageConnection,
            ],
        ] + self::MAIN_REPOSITORY_CONFIG;
    }
}
