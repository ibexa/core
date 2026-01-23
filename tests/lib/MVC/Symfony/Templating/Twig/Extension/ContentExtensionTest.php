<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\Helper\FieldsGroups\FieldsGroupsList;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\ContentExtension;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests ContentExtension in the context of site with "fre-FR, eng-US" configured as languages.

 *
 * @phpstan-import-type TFieldsData from \Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension\FileSystemTwigIntegrationTestCase
 */
class ContentExtensionTest extends FileSystemTwigIntegrationTestCase
{
    private FieldHelper & MockObject $fieldHelperMock;

    public function getExtensions(): array
    {
        $this->fieldHelperMock = $this->createMock(FieldHelper::class);
        $configResolver = $this->getConfigResolverMock();

        return [
            new ContentExtension(
                $this->getRepositoryMock(),
                new TranslationHelper(
                    $configResolver,
                    $this->createMock(ContentService::class),
                    [],
                    $this->createMock(LoggerInterface::class)
                ),
                $this->fieldHelperMock,
                $this->getFieldsGroupsListMock()
            ),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/_fixtures/content_functions/';
    }

    /**
     * Creates content with initial/main language being fre-FR.
     *
     * @param TFieldsData $fieldsData
     * @param array<string, string> $namesData
     */
    protected function getContent(
        string $contentTypeIdentifier,
        array $fieldsData,
        array $namesData = []
    ): Content {
        $contentTypeId = $this->getContentTypeId($contentTypeIdentifier);

        $fields = $this->buildFieldsFromData($fieldsData, $contentTypeIdentifier);

        return new Content(
            [
                'internalFields' => $fields,
                'versionInfo' => new VersionInfo(
                    [
                        'versionNo' => 64,
                        'names' => $namesData,
                        'initialLanguageCode' => 'fre-FR',
                        'contentInfo' => new ContentInfo(
                            [
                                'id' => 42,
                                'mainLanguageCode' => 'fre-FR',
                                // Using as id as we don't really care to test the service here
                                'contentTypeId' => $contentTypeId,
                            ]
                        ),
                    ]
                ),
                'contentType' => new ContentType([
                    'fieldDefinitions' => new FieldDefinitionCollection($this->fieldDefinitions[$contentTypeIdentifier] ?? []),
                ]),
            ]
        );
    }

    /**
     * @param array<string, mixed>  $fieldsData
     * @param array<mixed>  $namesData
     */
    protected function getContentAwareObject(
        string $contentTypeIdentifier,
        array $fieldsData,
        array $namesData = []
    ): ContentAwareInterface {
        $content = $this->getContent($contentTypeIdentifier, $fieldsData, $namesData);

        $mock = $this->createMock(ContentAwareInterface::class);
        $mock->method('getContent')->willReturn($content);

        return $mock;
    }

    private function getFieldsGroupsListMock(): FieldsGroupsList
    {
        $fieldsGroupsList = $this->createMock(FieldsGroupsList::class);
        $fieldsGroupsList->method('getGroups')->willReturn([
            'content' => 'Content',
        ]);

        return $fieldsGroupsList;
    }

    protected function getField(bool $isEmpty): Field
    {
        $field = new Field(['fieldDefIdentifier' => 'testfield', 'value' => null]);

        $this->fieldHelperMock
            ->expects(self::once())
            ->method('isFieldEmpty')
            ->willReturn($isEmpty);

        return $field;
    }

    protected function getRepositoryMock(): Repository & MockObject
    {
        $mock = $this->createMock(Repository::class);

        $mock
            ->method('getContentTypeService')
            ->willReturn($this->getContentTypeServiceMock());

        return $mock;
    }

    protected function getContentTypeServiceMock(): ContentTypeService & MockObject
    {
        $mock = $this->createMock(ContentTypeService::class);

        $mock
            ->method('loadContentType')
            ->willReturnCallback(
                function ($contentTypeId): ContentType {
                    $contentTypeIdentifier = $this->getContentTypeIdentifier($contentTypeId);

                    return new ContentType(
                        [
                            'identifier' => $contentTypeId,
                            'mainLanguageCode' => 'fre-FR',
                            'fieldDefinitions' => new FieldDefinitionCollection(
                                $this->fieldDefinitions[$contentTypeIdentifier]
                            ),
                        ]
                    );
                }
            );

        return $mock;
    }
}
