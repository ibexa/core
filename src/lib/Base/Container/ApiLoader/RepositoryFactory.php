<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\ApiLoader;

use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Persistence\Filter\Content\Handler as ContentFilteringHandler;
use Ibexa\Contracts\Core\Persistence\Filter\Location\Handler as LocationFilteringHandler;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Persistence\TransactionHandler;
use Ibexa\Contracts\Core\Repository\LanguageResolver;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\PermissionService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Validator\ContentValidator;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\Collector\ContentCollector;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Mapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\ProxyFactory\ProxyDomainMapperFactoryInterface;
use Ibexa\Core\Repository\Repository as CoreRepository;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Search\Common\BackgroundIndexer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @phpstan-import-type TPolicyMap from \Ibexa\Contracts\Core\Repository\RoleService
 */
final class RepositoryFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @phpstan-var TPolicyMap */
    private array $policyMap;

    private LanguageResolver $languageResolver;

    private RepositoryConfigurationProviderInterface $repositoryConfigurationProvider;

    /**
     * @phpstan-param TPolicyMap $policyMap
     */
    public function __construct(
        array $policyMap,
        LanguageResolver $languageResolver,
        RepositoryConfigurationProviderInterface $repositoryConfigurationProvider
    ) {
        $this->policyMap = $policyMap;
        $this->languageResolver = $languageResolver;
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
    }

    /**
     * Builds the main repository, heart of Ibexa API.
     *
     * This always returns the true inner Repository, please depend on ezpublish.api.repository and not this method
     * directly to make sure you get an instance wrapped inside Event / Cache / * functionality.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function buildRepository(
        PersistenceHandler $persistenceHandler,
        SearchHandler $searchHandler,
        BackgroundIndexer $backgroundIndexer,
        RelationProcessor $relationProcessor,
        FieldTypeRegistry $fieldTypeRegistry,
        PasswordHashService $passwordHashService,
        ProxyDomainMapperFactoryInterface $proxyDomainMapperFactory,
        Mapper\ContentDomainMapper $contentDomainMapper,
        Mapper\ContentTypeDomainMapper $contentTypeDomainMapper,
        Mapper\RoleDomainMapper $roleDomainMapper,
        Mapper\ContentMapper $contentMapper,
        ContentValidator $contentValidator,
        LimitationService $limitationService,
        PermissionService $permissionService,
        ContentFilteringHandler $contentFilteringHandler,
        LocationFilteringHandler $locationFilteringHandler,
        PasswordValidatorInterface $passwordValidator,
        ConfigResolverInterface $configResolver,
        NameSchemaServiceInterface $nameSchemaService,
        TransactionHandler $transactionHandler,
        ContentCollector $contentCollector,
        ValidatorInterface $validator,
    ): Repository {
        $config = $this->repositoryConfigurationProvider->getRepositoryConfig();

        if (isset($config['password_hash'])) {
            $passwordHashService->setDefaultHashType($config['password_hash']['default_type'] ?? User::PASSWORD_HASH_PHP_DEFAULT);
            $passwordHashService->setUpdateTypeOnChange($config['password_hash']['update_type_on_change'] ?? false);
        }

        return new CoreRepository(
            $persistenceHandler,
            $searchHandler,
            $backgroundIndexer,
            $relationProcessor,
            $fieldTypeRegistry,
            $passwordHashService,
            $proxyDomainMapperFactory,
            $contentDomainMapper,
            $contentTypeDomainMapper,
            $roleDomainMapper,
            $contentMapper,
            $contentValidator,
            $limitationService,
            $this->languageResolver,
            $permissionService,
            $contentFilteringHandler,
            $locationFilteringHandler,
            $passwordValidator,
            $configResolver,
            $nameSchemaService,
            $transactionHandler,
            $contentCollector,
            $validator,
            [
                'role' => [
                    'policyMap' => $this->policyMap,
                ],
                'languages' => $configResolver->getParameter('languages'),
                'content' => [
                    'default_version_archive_limit' => $config['options']['default_version_archive_limit'],
                    'remove_archived_versions_on_publish' => $config['options']['remove_archived_versions_on_publish'],
                    'grace_period_in_seconds' => $config['options']['grace_period_in_seconds'] ?? (int) ini_get('max_execution_time'),
                ],
            ],
            $this->logger ?? new NullLogger()
        );
    }

    /**
     * Returns a service based on a name string (content => contentService, etc).
     *
     * @param \Ibexa\Contracts\Core\Repository\Repository $repository
     * @param string $serviceName
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return mixed
     */
    public function buildService(Repository $repository, $serviceName)
    {
        $methodName = 'get' . $serviceName . 'Service';
        if (!method_exists($repository, $methodName)) {
            throw new InvalidArgumentException($serviceName, 'No such service');
        }

        return $repository->$methodName();
    }
}
