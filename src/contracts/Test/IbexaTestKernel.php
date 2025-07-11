<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\DBAL\Connection;
use FOS\JsRoutingBundle\FOSJsRoutingBundle;
use Ibexa\Bundle\Core\IbexaCoreBundle;
use Ibexa\Bundle\LegacySearchEngine\IbexaLegacySearchEngineBundle;
use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Contracts\Core\Persistence\TransactionHandler;
use Ibexa\Contracts\Core\Repository;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\YamlFixture;
use Ibexa\Tests\Integration\Core\IO\FlysystemTestAdapter;
use Ibexa\Tests\Integration\Core\IO\FlysystemTestAdapterInterface;
use JMS\TranslationBundle\JMSTranslationBundle;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Liip\ImagineBundle\LiipImagineBundle;
use LogicException;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal For core tests only. Use \Ibexa\Contracts\Test\Core\IbexaTestKernel from ibexa/test-core instead.
 *
 * Baseline test kernel that dependent packages can extend for their integration tests.
 *
 * ## Configuring the kernel
 *
 * It automatically exposes all Repository-based services for consumption in tests (marking them as public prevents
 * them from being removed from test container). A minimal configuration Symfony framework configuration is provided,
 * along with Doctrine connection.
 *
 * To supply a different configuration, extend IbexaTestKernel::loadConfiguration() method.
 *
 * You can supply your own services (which is something you probably want) by extending IbexaTestKernel::loadServices().
 *
 * If you need even more control over how the container is built you can do that by extending the
 * IbexaTestKernel::registerContainerConfiguration().
 *
 * ## Adding bundles
 *
 * Bundles can be added by extending IbexaTestKernel::registerBundles() method (just like in any Kernel).
 *
 * ## Exposing your services
 *
 * To add services to the test Kernel and make them available in tests via IbexaKernelTestCase::getServiceByClassName(),
 * you'll need to extend IbexaTestKernel::getExposedServicesByClass() and / or IbexaTestKernel::getExposedServicesById()
 * method.
 *
 * IbexaTestKernel::getExposedServicesByClass() is a simpler variant provided for services that are registered in
 * service container using their FQCN.
 *
 * IbexaTestKernel::getExposedServicesById() is useful if your service is not registered as it's FQCN (for example,
 * if you have multiple services for the same class / interface).
 *
 * If don't need the repository services (or not all), you can replace the IbexaTestKernel::EXPOSED_SERVICES_BY_CLASS and
 * IbexaTestKernel::EXPOSED_SERVICES_BY_ID consts in extending class, without changing the methods above.
 */
class IbexaTestKernel extends Kernel implements IbexaTestKernelInterface
{
    /**
     * @var iterable<class-string>
     */
    protected const iterable EXPOSED_SERVICES_BY_CLASS = [
        TransactionHandler::class,
        Connection::class,
        Repository\Repository::class,
        Repository\ContentService::class,
        Repository\ContentTypeService::class,
        Repository\LanguageService::class,
        Repository\LocationService::class,
        Repository\ObjectStateService::class,
        Repository\PermissionResolver::class,
        Repository\RoleService::class,
        Repository\SearchService::class,
        Repository\SectionService::class,
        Repository\UserService::class,
        Repository\TokenService::class,
        Repository\URLAliasService::class,
        Repository\BookmarkService::class,
        Repository\TrashService::class,
        Handler::class,
    ];

    /**
     * @var iterable<string, class-string>
     */
    protected const iterable EXPOSED_SERVICES_BY_ID = [];

    /**
     * @return string a service ID that service aliases will be registered as
     */
    public static function getAliasServiceId(string $id): string
    {
        return 'test.' . $id;
    }

    /**
     * @return iterable<string>
     */
    public function getSchemaFiles(): iterable
    {
        yield $this->locateResource('@IbexaCoreBundle/Resources/config/storage/legacy/schema.yaml');
    }

    /**
     * @return iterable<\Ibexa\Contracts\Core\Test\Persistence\Fixture>
     */
    public function getFixtures(): iterable
    {
        yield new YamlFixture(dirname(__DIR__, 3) . '/tests/integration/Core/Repository/_fixtures/Legacy/data/test_data.yaml');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/ibexa-test-kernel/' . md5(serialize(getenv())) . md5(static::class);
    }

    public function getBuildDir(): string
    {
        return sys_get_temp_dir() . '/ibexa-test-kernel-build/' . md5(serialize(getenv())) . md5(static::class);
    }

    public function registerBundles(): iterable
    {
        yield new SecurityBundle();
        yield new IbexaCoreBundle();
        yield new IbexaLegacySearchEngineBundle();
        yield new JMSTranslationBundle();
        yield new FOSJsRoutingBundle();
        yield new FrameworkBundle();
        yield new LiipImagineBundle();
        yield new TwigBundle();
        yield new DoctrineBundle();
    }

    /**
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->setParameter('ibexa.core.test.resource_dir', self::getResourcesPath());
            $container->setParameter('ibexa.kernel.root_dir', dirname(__DIR__, 3));
        });

        $this->loadConfiguration($loader);
        $this->loadServices($loader);

        $loader->load(static function (ContainerBuilder $container): void {
            self::prepareIOServices($container);
            self::createPublicAliasesForServicesUnderTest($container);
            self::setUpTestLogger($container);
        });
    }

    /**
     * @throws \Exception
     */
    protected function loadConfiguration(LoaderInterface $loader): void
    {
        $loader->load(self::getResourcesPath() . '/config/doctrine.php');
        $loader->load(self::getResourcesPath() . '/config/ezpublish.yaml');
        $loader->load(self::getResourcesPath() . '/config/framework.yaml');
        $this->loadSecurity($loader);
    }

    /**
     * @throws \Exception
     */
    protected function loadServices(LoaderInterface $loader): void
    {
        $loader->load(self::getResourcesPath() . '/services/fixture-services.yaml');
    }

    /**
     * @throws \Exception
     */
    protected function loadSecurity(LoaderInterface $loader): void
    {
        $loader->load(self::getResourcesPath() . '/config/security.yaml');
    }

    /**
     * @return iterable<class-string>
     */
    protected static function getExposedServicesByClass(): iterable
    {
        return static::EXPOSED_SERVICES_BY_CLASS;
    }

    /**
     * @return iterable<string, class-string>
     */
    protected static function getExposedServicesById(): iterable
    {
        return static::EXPOSED_SERVICES_BY_ID;
    }

    private static function getResourcesPath(): string
    {
        return dirname(__DIR__, 3) . '/tests/bundle/Core/Resources';
    }

    private static function prepareIOServices(ContainerBuilder $container): void
    {
        if (!class_exists(InMemoryFilesystemAdapter::class)) {
            throw new LogicException(
                sprintf(
                    'Missing %s class. Ensure that %s package is installed as a dev dependency',
                    InMemoryFilesystemAdapter::class,
                    'league/flysystem-memory',
                )
            );
        }

        $container->setParameter('webroot_dir', dirname(__DIR__, 3) . '/var/public');
        $inMemoryAdapter = new Definition(InMemoryFilesystemAdapter::class);
        $container->setDefinition(InMemoryFilesystemAdapter::class, $inMemoryAdapter);

        $testAdapterDefinition = new Definition(FlysystemTestAdapter::class);
        $testAdapterDefinition->setDecoratedService(
            'ibexa.core.io.flysystem.adapter.site_access_aware',
            null,
            -10
        );
        $testAdapterDefinition->setArgument('$inMemoryAdapter', $inMemoryAdapter);
        $testAdapterDefinition->setArgument(
            '$localAdapter',
            new Reference(
                '.inner'
            )
        );
        $testAdapterDefinition->setPublic(true);
        $container->setDefinition(
            FlysystemTestAdapterInterface::class,
            $testAdapterDefinition
        );
    }

    private static function createPublicAliasesForServicesUnderTest(ContainerBuilder $container): void
    {
        foreach (static::getExposedServicesByClass() as $className) {
            $container->setAlias(static::getAliasServiceId($className), $className)
                ->setPublic(true);
        }

        foreach (static::getExposedServicesById() as $id => $className) {
            $container->setAlias(static::getAliasServiceId($id), $id)
                ->setPublic(true);
        }
    }

    private static function setUpTestLogger(ContainerBuilder $container): void
    {
        $container->setDefinition('logger', new Definition(NullLogger::class));
    }

    /**
     * Creates synthetic services in container, allowing compilation of container when some services are missing.
     * Additionally, those services can be replaced with mock implementations at runtime, allowing integration testing.
     *
     * You can set them up in KernelTestCase by calling `self::getContainer()->set($id, $this->createMock($class));`
     *
     * @phpstan-param class-string $class
     */
    protected static function addSyntheticService(ContainerBuilder $container, string $class, ?string $id = null): void
    {
        $id = $id ?? $class;
        if ($container->has($id)) {
            throw new LogicException(sprintf(
                'Expected test kernel to not contain "%s" service. A real service should not be overwritten by a mock',
                $id,
            ));
        }

        $definition = new Definition($class);
        $definition->setSynthetic(true);
        $container->setDefinition($id, $definition);
    }
}
