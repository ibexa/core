<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\ContainerConfigurator;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ContainerConfiguratorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;

class Generic implements ContainerConfiguratorInterface
{
    /**
     * @throws \Exception
     */
    public function configure(ContainerBuilder $container): void
    {
        // One of `legacy` (default) or `solr`
        $container->setParameter('search_engine', '%env(SEARCH_ENGINE)%');

        // Session save path as used by symfony session handlers (e.g. used for dsn with redis)
        $container->setParameter('ibexa.session.save_path', '%kernel.project_dir%/var/sessions/%kernel.environment%');

        // Predefined pools are located in config/packages/cache_pool/
        // You can add your own cache pool to the folder mentioned above.
        // In order to change the default cache_pool use environmental variable export.
        // The line below must not be altered as required cache service files are resolved based on environmental config.
        $container->setParameter('cache_pool', '%env(CACHE_POOL)%');

        // By default, cache ttl is set to 24h, when using Varnish you can set a much higher value. High values depends on
        // using IbexaHttpCacheBundle (default as of v1.12) which by design expires affected cache on changes
        $container->setParameter('httpcache_default_ttl', '%env(HTTPCACHE_DEFAULT_TTL)%');

        // Settings for HttpCache
        $container->setParameter('purge_server', '%env(HTTPCACHE_PURGE_SERVER)%');

        // Identifier used to generate the CSRF token. Commenting this line will result in authentication
        // issues both in AdminUI and REST calls
        $container->setParameter('ibexa.rest.csrf_token_intention', 'authenticate');

        // Varnish invalidation/purge token (for use on platform.sh, Ibexa Cloud and other places you can't use IP for ACL)
        $container->setParameter('varnish_invalidate_token', '%env(resolve:default::HTTPCACHE_VARNISH_INVALIDATE_TOKEN)%');

        // Compile time handlers
        // These are defined at compile time, and hence can't be set at runtime using env()
        // config/env/generic.php takes care about letting you set them by env variables

        // Session handler, by default set to file based (instead of ~) in order to be able to use %ibexa.session.save_path%
        $container->setParameter('ibexa.session.handler_id', 'session.handler.native_file');

        // Purge type used by HttpCache system ("local", "varnish"/"http", and on ee also "fastly")
        $container->setParameter('purge_type', '%env(HTTPCACHE_PURGE_TYPE)%');

        $container->setParameter('solr_dsn', '%env(SOLR_DSN)%');
        $container->setParameter('solr_core', '%env(SOLR_CORE)%');

        $projectDir = $container->getParameter('kernel.project_dir');

        if (null !== ($dfsNfsPath = $_SERVER['DFS_NFS_PATH'] ?? null)) {
            $container->setParameter('dfs_nfs_path', $dfsNfsPath);

            $parameterMap = [
                'dfs_database_charset' => 'database_charset',
                'dfs_database_driver' => 'database_driver',
                'dfs_database_collation' => 'database_collation',
            ];

            foreach ($parameterMap as $dfsParameter => $platformParameter) {
                $container->setParameter(
                    $dfsParameter,
                    $_SERVER[strtoupper($dfsParameter)] ?? $container->getParameter($platformParameter)
                );
            }

            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/dfs'));
            $loader->load('dfs.yaml');
        }

        // Cache settings
        // If CACHE_POOL env variable is set, check if there is a yml file that needs to be loaded for it
        if (
            null !== ($pool = $_SERVER['CACHE_POOL'] ?? null) &&
            file_exists($projectDir . "/config/packages/cache_pool/${pool}.yaml")
        ) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/cache_pool'));
            $loader->load($pool . '.yaml');
        }

        if (null !== ($purgeType = $_SERVER['HTTPCACHE_PURGE_TYPE'] ?? null)) {
            $container->setParameter('purge_type', $purgeType);
            $container->setParameter('ibexa.http_cache.purge_type', $purgeType);
        }

        if (null !== ($value = $_SERVER['MAILER_TRANSPORT'] ?? null)) {
            $container->setParameter('mailer_transport', $value);
        }

        if (null !== ($value = $_SERVER['LOG_TYPE'] ?? null)) {
            $container->setParameter('log_type', $value);
        }

        if (null !== ($value = $_SERVER['SESSION_HANDLER_ID'] ?? null)) {
            $container->setParameter('ibexa.session.handler_id', $value);
        }

        if (null !== ($value = $_SERVER['SESSION_SAVE_PATH'] ?? null)) {
            $container->setParameter('ibexa.session.save_path', $value);
        }
    }
}
