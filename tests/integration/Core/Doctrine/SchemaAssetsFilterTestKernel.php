<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Doctrine;

use Doctrine\DBAL\Configuration;
use Ibexa\Contracts\Core\Test\IbexaTestKernel;
use Ibexa\Tests\Integration\Core\Doctrine\Stub\SchemaFilterEntityBundle\SchemaFilterEntityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers ORM-mapped entities on the ibexa_default entity manager and, on top of that,
 * a connection-level schema filter of the kind a project can configure. Both are needed to
 * tell apart the two filters that end up on the same DBAL connection Configuration.
 */
final class SchemaAssetsFilterTestKernel extends IbexaTestKernel
{
    public const string DBAL_CONFIGURATION_SERVICE_ID = 'doctrine.dbal.default_connection.configuration';

    public const string MANAGED_TABLE = 'ibexa_test_managed_entity';

    public const string FILTERED_OUT_TABLE = 'ibexa_test_filtered_out_entity';

    private const string ENTITY_NAMESPACE = 'Ibexa\Tests\Integration\Core\Doctrine\Stub\SchemaFilterEntityBundle\Entity';

    /**
     * @var iterable<string, class-string>
     */
    protected const iterable EXPOSED_SERVICES_BY_ID = [
        self::DBAL_CONFIGURATION_SERVICE_ID => Configuration::class,
    ];

    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();
        yield new SchemaFilterEntityBundle();
    }

    protected function loadConfiguration(LoaderInterface $loader): void
    {
        parent::loadConfiguration($loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('ibexa', [
                'orm' => [
                    'entity_mappings' => [
                        'SchemaFilterEntityBundle' => [
                            'type' => 'attribute',
                            'dir' => 'Entity',
                            'prefix' => self::ENTITY_NAMESPACE,
                        ],
                    ],
                ],
            ]);

            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'schema_filter' => sprintf('~^(?!%s$)~', self::FILTERED_OUT_TABLE),
                ],
            ]);
        });
    }
}
