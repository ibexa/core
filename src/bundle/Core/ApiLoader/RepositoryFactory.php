<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\ApiLoader;

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
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\Collector\ContentCollector;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Mapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\ProxyFactory\ProxyDomainMapperFactoryInterface;
use Ibexa\Core\Repository\Repository as CoreRepository;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Search\Common\BackgroundIndexer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @deprecated 5.0.0 The "\Ibexa\Bundle\Core\ApiLoader\RepositoryFactory" class is deprecated, will be removed in 6.0.
 * Use {@see \Ibexa\Core\Base\Container\ApiLoader\RepositoryFactory instead}.
 */
class RepositoryFactory
{
    /** @var ConfigResolverInterface */
    private $configResolver;

    /**
     * Map of system configured policies.
     *
     * @var array
     */
    private $policyMap;

    /** @var LoggerInterface */
    private $logger;

    /** @var LanguageResolver */
    private $languageResolver;

    public function __construct(
        ConfigResolverInterface $configResolver,
        array $policyMap,
        LanguageResolver $languageResolver,
        private readonly RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
        ?LoggerInterface $logger = null
    ) {
        $this->configResolver = $configResolver;
        $this->policyMap = $policyMap;
        $this->languageResolver = $languageResolver;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Builds the main repository, heart of Ibexa API.
     *
     * This always returns the true inner Repository, please depend on ibexa.api.repository and not this method
     * directly to make sure you get an instance wrapped inside Event / Cache / * functionality.
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
        ValidatorInterface $validator
    ): Repository {
        $config = $this->repositoryConfigurationProvider->getRepositoryConfig();

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
                'languages' => $this->configResolver->getParameter('languages'),
                'content' => [
                    'default_version_archive_limit' => $config['options']['default_version_archive_limit'],
                    'remove_archived_versions_on_publish' => $config['options']['remove_archived_versions_on_publish'],
                    'grace_period_in_seconds' => $config['options']['grace_period_in_seconds'] ?? (int) ini_get('max_execution_time'),
                ],
            ],
            $this->logger
        );
    }
}
