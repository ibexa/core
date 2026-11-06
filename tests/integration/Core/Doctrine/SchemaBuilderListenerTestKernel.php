<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Bundle\DoctrineSchema\DoctrineSchemaBundle;
use Ibexa\Bundle\RepositoryInstaller\IbexaRepositoryInstallerBundle;
use Ibexa\Contracts\Core\Test\IbexaTestKernel;
use Ibexa\Tests\Integration\Core\Doctrine\Stub\SchemaFilterEntityBundle\SchemaFilterEntityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers an ORM-mapped entity on the ibexa_default entity manager, so that the ORM schema
 * differs from the legacy schema.yaml tables sharing its connection.
 */
final class SchemaBuilderListenerTestKernel extends IbexaTestKernel
{
    public const string ENTITY_MANAGER_SERVICE_ID = 'doctrine.orm.ibexa_default_entity_manager';

    public const string MANAGED_TABLE = 'ibexa_test_managed_entity';

    private const string ENTITY_NAMESPACE = 'Ibexa\Tests\Integration\Core\Doctrine\Stub\SchemaFilterEntityBundle\Entity';

    /**
     * @var iterable<string, class-string>
     */
    protected const iterable EXPOSED_SERVICES_BY_ID = [
        self::ENTITY_MANAGER_SERVICE_ID => EntityManagerInterface::class,
    ];

    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();
        yield new DoctrineSchemaBundle();
        yield new IbexaRepositoryInstallerBundle();
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
        });
    }
}
