<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository;

use Ibexa\Contracts\Core\Persistence\Filter\Content\Handler as ContentFilteringHandler;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\PermissionService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Validator\ContentValidator;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\Collector\ContentCollector;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Mapper\ContentDomainMapper;
use Ibexa\Core\Repository\Mapper\ContentMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
final class ContentServiceTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    protected function setUp(): void
    {
        $this->contentService = new ContentService(
            $this->createStub(Repository::class),
            $this->createStub(PersistenceHandler::class),
            $this->createStub(ContentDomainMapper::class),
            $this->createStub(RelationProcessor::class),
            $this->createStub(NameSchemaServiceInterface::class),
            $this->createStub(FieldTypeRegistry::class),
            $this->createStub(PermissionService::class),
            $this->createStub(ContentMapper::class),
            $this->createStub(ContentValidator::class),
            $this->createStub(ContentFilteringHandler::class),
            new ContentCollector(),
            $this->createStub(ValidatorInterface::class),
        );
    }

    public function testFindDoesNotModifyFilter(): void
    {
        $filter = new Filter();
        $originalFilter = clone $filter;
        $this->contentService->find($filter, ['eng-GB']);
        self::assertEquals($originalFilter, $filter);
    }

    public function testCountDoesNotModifyFilter(): void
    {
        $filter = new Filter();
        $originalFilter = clone $filter;
        $this->contentService->count($filter, ['eng-GB']);
        self::assertEquals($originalFilter, $filter);
    }
}
