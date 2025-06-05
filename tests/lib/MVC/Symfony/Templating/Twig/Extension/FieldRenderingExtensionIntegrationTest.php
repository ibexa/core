<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
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
use Psr\Log\LoggerInterface;
use Twig\Environment;

class FieldRenderingExtensionIntegrationTest extends FileSystemTwigIntegrationTestCase
{
    private const int EXAMPLE_FIELD_DEFINITION_ID = 2;
    private const int EXAMPLE_CONTENT_TYPE_ID = 32;

    private $fieldDefinitions = [];

    public function getExtensions()
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

    public function getFieldDefinition($typeIdentifier, $id = null, $settings = [])
    {
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
     * @param string $contentTypeIdentifier
     * @param array $fieldsData
     * @param array $namesData
     *
     * @return \Ibexa\Core\Repository\Values\Content\Content
     */
    protected function getContent($contentTypeIdentifier, array $fieldsData, array $namesData = [])
    {
        $fields = [];
        foreach ($fieldsData as $fieldTypeIdentifier => $fieldsArray) {
            $fieldsArray = isset($fieldsArray['id']) ? [$fieldsArray] : $fieldsArray;
            foreach ($fieldsArray as $fieldInfo) {
                // Save field definitions in property for mocking purposes
                $this->fieldDefinitions[$contentTypeIdentifier][$fieldInfo['fieldDefIdentifier']] = new FieldDefinition(
                    [
                        'identifier' => $fieldInfo['fieldDefIdentifier'],
                        'id' => $fieldInfo['id'],
                        'fieldTypeIdentifier' => $fieldTypeIdentifier,
                        'names' => isset($fieldInfo['fieldDefNames']) ? $fieldInfo['fieldDefNames'] : [],
                        'descriptions' => isset($fieldInfo['fieldDefDescriptions']) ? $fieldInfo['fieldDefDescriptions'] : [],
                    ]
                );
                unset($fieldInfo['fieldDefNames'], $fieldInfo['fieldDefDescriptions']);
                $fields[] = new Field($fieldInfo);
            }
        }
        $content = new Content(
            [
                'internalFields' => $fields,
                'contentType' => new ContentType([
                    'id' => self::EXAMPLE_CONTENT_TYPE_ID,
                    'identifier' => $contentTypeIdentifier,
                    'mainLanguageCode' => 'fre-FR',
                    'fieldDefinitions' => new FieldDefinitionCollection(
                        $this->fieldDefinitions[$contentTypeIdentifier
                    ]
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
                                // Using as id as we don't really care to test the service here
                                'contentTypeId' => $contentTypeIdentifier,
                            ]
                        ),
                    ]
                ),
            ]
        );

        return $content;
    }

    /**
     * @param array<mixed>  $fieldsData
     * @param array<mixed>  $namesData
     */
    protected function getContentAwareObject(string $contentTypeIdentifier, array $fieldsData, array $namesData = []): ContentAwareInterface
    {
        $content = $this->getContent($contentTypeIdentifier, $fieldsData, $namesData);

        $mock = $this->createMock(ContentAwareInterface::class);
        $mock->method('getContent')->willReturn($content);

        return $mock;
    }

    private function getTemplatePath($tpl): string
    {
        return 'templates/' . $tpl;
    }

    private function getConfigResolverMock()
    {
        $mock = $this->createMock(ConfigResolverInterface::class);
        // Signature: ConfigResolverInterface->getParameter( $paramName, $namespace = null, $scope = null )
        $mock->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        [
                            'languages',
                            null,
                            null,
                            ['fre-FR', 'eng-US'],
                        ],
                    ]
                )
            );

        return $mock;
    }

    /**
     * @return \Ibexa\Core\MVC\Symfony\Templating\Twig\ResourceProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getResourceProviderMock(): ResourceProviderInterface
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
