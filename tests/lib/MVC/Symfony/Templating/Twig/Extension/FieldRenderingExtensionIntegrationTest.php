<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderRegistryInterface;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\FieldRenderingExtension;
use Ibexa\Core\MVC\Symfony\Templating\Twig\FieldBlockRenderer;
use Ibexa\Core\MVC\Symfony\Templating\Twig\ResourceProviderInterface;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * @phpstan-import-type TFieldsData from \Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension\FileSystemTwigIntegrationTestCase
 */
class FieldRenderingExtensionIntegrationTest extends FileSystemTwigIntegrationTestCase
{
    private const int EXAMPLE_FIELD_DEFINITION_ID = 2;

    public function getExtensions(): array
    {
        $configResolver = $this->getConfigResolverMock();
        $twig = $this->createMock(Environment::class);
        $resourceProvider = $this->getResourceProviderMock();

        $fieldBlockRenderer = new FieldBlockRenderer(
            $twig,
            $resourceProvider,
            $this->getTemplatePath('base.html.twig')
        );

        return [
            new FieldRenderingExtension(
                $fieldBlockRenderer,
                $this->createMock(ParameterProviderRegistryInterface::class),
                new TranslationHelper(
                    $configResolver,
                    $this->createMock(ContentService::class),
                    [],
                    $this->createMock(LoggerInterface::class)
                )
            ),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/_fixtures/field_rendering_functions/';
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function getFieldDefinition(
        string $typeIdentifier,
        ?int $id = null,
        array $settings = []
    ): FieldDefinition {
        return new FieldDefinition(
            [
                'id' => $id ?? self::EXAMPLE_FIELD_DEFINITION_ID,
                'fieldSettings' => $settings,
                'fieldTypeIdentifier' => $typeIdentifier,
            ]
        );
    }

    /**
     * Creates content with initial/main language being fre-FR.
     *
     * @phpstan-param TFieldsData $fieldsData
     *
     * @param array<string, string> $namesData
     */
    protected function getContent(
        string $contentTypeIdentifier,
        array $fieldsData,
        array $namesData = []
    ): Content {
        $fields = $this->buildFieldsFromData($fieldsData, $contentTypeIdentifier);

        return new Content(
            [
                'internalFields' => $fields,
                'contentType' => new ContentType([
                    'id' => $this->getContentTypeId($contentTypeIdentifier),
                    'identifier' => $contentTypeIdentifier,
                    'mainLanguageCode' => 'fre-FR',
                    'fieldDefinitions' => new FieldDefinitionCollection(
                        $this->fieldDefinitions[$contentTypeIdentifier]
                    ),
                ]),
                'versionInfo' => new VersionInfo(
                    [
                        'versionNo' => 64,
                        'names' => $namesData,
                        'initialLanguageCode' => 'fre-FR',
                        'contentInfo' => new ContentInfo(
                            [
                                'id' => 42,
                                'mainLanguageCode' => 'fre-FR',
                                'contentTypeId' => $this->getContentTypeId($contentTypeIdentifier),
                            ]
                        ),
                    ]
                ),
            ]
        );
    }

    /**
     * @param array<mixed> $fieldsData
     * @param array<mixed> $namesData
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

    private function getTemplatePath(string $tpl): string
    {
        return 'templates/' . $tpl;
    }

    private function getResourceProviderMock(): ResourceProviderInterface & MockObject
    {
        $mock = $this->createMock(ResourceProviderInterface::class);

        $mock
            ->method('getFieldViewResources')
            ->willReturn([
                [
                    'template' => $this->getTemplatePath('fields_override2.html.twig'),
                    'priority' => 20,
                ],
                [
                    'template' => $this->getTemplatePath('fields_override1.html.twig'),
                    'priority' => 10,
                ],
                [
                    'template' => $this->getTemplatePath('fields_default.html.twig'),
                    'priority' => 0,
                ],
            ])
        ;

        $mock
            ->method('getFieldDefinitionViewResources')
            ->willReturn([
                [
                    'template' => $this->getTemplatePath('settings_override2.html.twig'),
                    'priority' => 20,
                ],
                [
                    'template' => $this->getTemplatePath('settings_override1.html.twig'),
                    'priority' => 10,
                ],
                [
                    'template' => $this->getTemplatePath('settings_default.html.twig'),
                    'priority' => 0,
                ],
            ])
        ;

        return $mock;
    }
}
