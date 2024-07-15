<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Contracts\Core\Persistence\Filter\Content\Handler as ContentFilteringHandler;
use Ibexa\Contracts\Core\Persistence\Filter\Location\Handler as LocationFilteringHandler;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Repository\LanguageResolver;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\PermissionService;
use Ibexa\Contracts\Core\Repository\Repository as APIRepository;
use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\ThumbnailStrategy;
use Ibexa\Contracts\Core\Repository\Validator\ContentValidator;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\FieldTypeService;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Mapper\ContentDomainMapper;
use Ibexa\Core\Repository\Mapper\ContentMapper;
use Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper;
use Ibexa\Core\Repository\Mapper\RoleDomainMapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\ProxyFactory\ProxyDomainMapperFactoryInterface;
use Ibexa\Core\Repository\Repository;
use Ibexa\Core\Repository\Strategy\ContentValidator\ContentValidatorStrategy;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Repository\Validator\ContentCreateStructValidator;
use Ibexa\Core\Repository\Validator\ContentUpdateStructValidator;
use Ibexa\Core\Repository\Validator\VersionValidator;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Core\Search\Common\BackgroundIndexer\NullIndexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Base test case for tests on services using Mock testing.
 */
abstract class Base extends TestCase
{
    private APIRepository $repository;

    private MockObject & APIRepository $repositoryMock;

    private MockObject & PermissionService $permissionServiceMock;

    private MockObject & PersistenceHandler $persistenceMock;

    private MockObject & ThumbnailStrategy $thumbnailStrategyMock;

    /**
     * The Content / Location / Search ... handlers for the persistence / Search / ... handler mocks.
     *
     * @var array<string, \PHPUnit\Framework\MockObject\MockObject&object> Key is relative to "Ibexa\Contracts\Core\"
     *
     * @see getPersistenceMockHandler()
     */
    private array $spiMockHandlers = [];

    private MockObject & ContentTypeDomainMapper $contentTypeDomainMapperMock;

    private MockObject & ContentDomainMapper $contentDomainMapperMock;

    private MockObject & LimitationService $limitationServiceMock;

    private MockObject & LanguageResolver $languageResolverMock;

    protected MockObject & RoleDomainMapper $roleDomainMapperMock;

    protected MockObject & ContentMapper $contentMapperMock;

    protected MockObject & ContentValidator $contentValidatorStrategyMock;

    private MockObject & ContentFilteringHandler $contentFilteringHandlerMock;

    private MockObject & LocationFilteringHandler $locationFilteringHandlerMock;

    protected MockObject & FieldTypeService $fieldTypeServiceMock;

    protected MockObject & FieldTypeRegistry $fieldTypeRegistryMock;

    protected MockObject & EventDispatcherInterface $eventDispatcher;

    /**
     * Get Real repository with mocked dependencies.
     *
     * @param array<string, mixed> $serviceSettings If set then non-shared instance of Repository is returned
     */
    protected function getRepository(array $serviceSettings = []): APIRepository
    {
        if (!isset($this->repository) || !empty($serviceSettings)) {
            $repository = new Repository(
                $this->getPersistenceMock(),
                $this->getSPIMockHandler('Search\\Handler'),
                new NullIndexer(),
                $this->getRelationProcessorMock(),
                $this->getFieldTypeRegistryMock(),
                $this->createMock(PasswordHashService::class),
                $this->getThumbnailStrategy(),
                $this->createMock(ProxyDomainMapperFactoryInterface::class),
                $this->getContentDomainMapperMock(),
                $this->getContentTypeDomainMapperMock(),
                $this->getRoleDomainMapperMock(),
                $this->getContentMapper(),
                $this->getContentValidatorStrategy(),
                $this->getLimitationServiceMock(),
                $this->getLanguageResolverMock(),
                $this->getPermissionServiceMock(),
                $this->getContentFilteringHandlerMock(),
                $this->getLocationFilteringHandlerMock(),
                $this->createMock(PasswordValidatorInterface::class),
                $this->createMock(ConfigResolverInterface::class),
                $this->createMock(NameSchemaServiceInterface::class),
                $serviceSettings,
            );

            if (!empty($serviceSettings)) {
                return $repository;
            }

            $this->repository = $repository;
        }

        return $this->repository;
    }

    protected function getFieldTypeServiceMock(): MockObject & FieldTypeService
    {
        if (!isset($this->fieldTypeServiceMock)) {
            $this->fieldTypeServiceMock = $this->createMock(FieldTypeService::class);
        }

        return $this->fieldTypeServiceMock;
    }

    protected function getFieldTypeRegistryMock(): MockObject & FieldTypeRegistry
    {
        if (!isset($this->fieldTypeRegistryMock)) {
            $this->fieldTypeRegistryMock = $this->createMock(FieldTypeRegistry::class);
        }

        return $this->fieldTypeRegistryMock;
    }

    protected function getEventDispatcher(): MockObject & EventDispatcherInterface
    {
        if (!isset($this->eventDispatcher)) {
            $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        return $this->eventDispatcher;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\ThumbnailStrategy|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getThumbnailStrategy()
    {
        if (!isset($this->thumbnailStrategyMock)) {
            $this->thumbnailStrategyMock = $this->createMock(ThumbnailStrategy::class);
        }

        return $this->thumbnailStrategyMock;
    }

    protected function getRepositoryMock(): MockObject & APIRepository
    {
        if (!isset($this->repositoryMock)) {
            $this->repositoryMock = $this->createMock(APIRepository::class);
        }

        return $this->repositoryMock;
    }

    protected function getPermissionResolverMock(): MockObject & PermissionResolver
    {
        return $this->getPermissionServiceMock();
    }

    protected function getPermissionServiceMock(): MockObject & PermissionService
    {
        if (!isset($this->permissionServiceMock)) {
            $this->permissionServiceMock = $this->createMock(PermissionService::class);
        }

        return $this->permissionServiceMock;
    }

    protected function getContentDomainMapperMock(): MockObject & ContentDomainMapper
    {
        if (!isset($this->contentDomainMapperMock)) {
            $this->contentDomainMapperMock = $this->createMock(ContentDomainMapper::class);
        }

        return $this->contentDomainMapperMock;
    }

    protected function getContentTypeDomainMapperMock(): MockObject & ContentTypeDomainMapper
    {
        if (!isset($this->contentTypeDomainMapperMock)) {
            $this->contentTypeDomainMapperMock = $this->createMock(ContentTypeDomainMapper::class);
        }

        return $this->contentTypeDomainMapperMock;
    }

    protected function getPersistenceMock(): MockObject & PersistenceHandler
    {
        if (!isset($this->persistenceMock)) {
            $this->persistenceMock = $this->createMock(PersistenceHandler::class);

            $this->persistenceMock
                ->method('contentHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\Handler'));

            $this->persistenceMock
                ->method('contentTypeHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\Type\\Handler'));

            $this->persistenceMock
                ->method('contentLanguageHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\Language\\Handler'));

            $this->persistenceMock
                ->method('locationHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\Location\\Handler'));

            $this->persistenceMock
                ->method('objectStateHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\ObjectState\\Handler'));

            $this->persistenceMock
                ->method('trashHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\Location\\Trash\\Handler'));

            $this->persistenceMock
                ->method('userHandler')
                ->willReturn($this->getPersistenceMockHandler('User\\Handler'));

            $this->persistenceMock
                ->method('sectionHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\Section\\Handler'));

            $this->persistenceMock
                ->method('urlAliasHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\UrlAlias\\Handler'));

            $this->persistenceMock
                ->method('urlWildcardHandler')
                ->willReturn($this->getPersistenceMockHandler('Content\\UrlWildcard\\Handler'));

            $this->persistenceMock
                ->method('urlWildcardHandler')
                ->willReturn($this->getPersistenceMockHandler('URL\\Handler'));
        }

        return $this->persistenceMock;
    }

    protected function getRelationProcessorMock(): MockObject & RelationProcessor
    {
        return $this->createMock(RelationProcessor::class);
    }

    /**
     * Returns a SPI Handler mock.
     *
     * @param string $handler For instance "Content\Type\Handler" or "Search\Handler", must be relative to "Ibexa\Contracts\Core"
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSPIMockHandler(string $handler): MockObject
    {
        if (!isset($this->spiMockHandlers[$handler])) {
            $interfaceFQCN = "Ibexa\\Contracts\\Core\\$handler";
            self::assertTrue(interface_exists($interfaceFQCN), "Interface $interfaceFQCN does not exist");
            $this->spiMockHandlers[$handler] = $this->createMock($interfaceFQCN);
        }

        return $this->spiMockHandlers[$handler];
    }

    /**
     * Returns a persistence Handler mock.
     *
     * @param string $handler For instance "Content\Type\Handler", must be relative to "Ibexa\Contracts\Core\Persistence"
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPersistenceMockHandler(string $handler): MockObject
    {
        return $this->getSPIMockHandler("Persistence\\$handler");
    }

    protected function getStubbedUser(int $id): APIUser
    {
        return new User(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            [
                                'contentInfo' => new ContentInfo(['id' => $id]),
                            ]
                        ),
                        'internalFields' => [],
                    ]
                ),
            ]
        );
    }

    protected function getLimitationServiceMock(): MockObject & LimitationService
    {
        if (!isset($this->limitationServiceMock)) {
            $this->limitationServiceMock = $this->createMock(LimitationService::class);
        }

        return $this->limitationServiceMock;
    }

    protected function getLanguageResolverMock(): LanguageResolver
    {
        if (!isset($this->languageResolverMock)) {
            $this->languageResolverMock = $this->createMock(LanguageResolver::class);
        }

        return $this->languageResolverMock;
    }

    /**
     * @param string[] $methods
     */
    protected function getRoleDomainMapperMock(array $methods = []): MockObject & RoleDomainMapper
    {
        if (!isset($this->roleDomainMapperMock)) {
            $mockBuilder = $this->getMockBuilder(RoleDomainMapper::class);
            if (!empty($methods)) {
                $mockBuilder->onlyMethods($methods);
            }
            $this->roleDomainMapperMock = $mockBuilder
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->roleDomainMapperMock;
    }

    protected function getContentMapper(): ContentMapper
    {
        return new ContentMapper(
            $this->getPersistenceMock()->contentLanguageHandler(),
            $this->getFieldTypeRegistryMock()
        );
    }

    protected function getContentValidatorStrategy(): ContentValidator
    {
        $validators = [
            new ContentCreateStructValidator(
                $this->getContentMapper(),
                $this->getFieldTypeRegistryMock()
            ),
            new ContentUpdateStructValidator(
                $this->getContentMapper(),
                $this->getFieldTypeRegistryMock(),
                $this->getPersistenceMock()->contentLanguageHandler()
            ),
            new VersionValidator(
                $this->getFieldTypeRegistryMock(),
            ),
        ];

        return new ContentValidatorStrategy($validators);
    }

    protected function getContentFilteringHandlerMock(): ContentFilteringHandler
    {
        if (!isset($this->contentFilteringHandlerMock)) {
            $this->contentFilteringHandlerMock = $this->createMock(ContentFilteringHandler::class);
        }

        return $this->contentFilteringHandlerMock;
    }

    private function getLocationFilteringHandlerMock(): LocationFilteringHandler
    {
        if (!isset($this->locationFilteringHandlerMock)) {
            $this->locationFilteringHandlerMock = $this->createMock(LocationFilteringHandler::class);
        }

        return $this->locationFilteringHandlerMock;
    }
}
