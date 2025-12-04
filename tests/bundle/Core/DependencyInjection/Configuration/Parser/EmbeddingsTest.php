<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Embeddings as EmbeddingsConfigParser;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\Yaml\Yaml;

final class EmbeddingsTest extends AbstractParserTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension([new EmbeddingsConfigParser()]),
        ];
    }

    /**
     * @return array<mixed>
     */
    protected function getMinimalConfiguration(): array
    {
        $input = file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_minimal.yml');

        if ($input === false) {
            self::fail('Failed to load ezpublish_minimal.yml');
        }

        return Yaml::parse($input);
    }

    public function testDefaultEmbeddingsSettings(): void
    {
        $this->load();

        $this->assertConfigResolverParameterValue('embedding_models', [], 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('default_embedding_model', 'text-embedding-ada-002', 'ibexa_demo_site');
    }

    /**
     * @param array<mixed> $config
     * @param array<mixed> $expected
     *
     * @dataProvider embeddingsSettingsProvider
     */
    public function testEmbeddingsSettings(array $config, array $expected): void
    {
        $this->load(
            [
                'system' => [
                    'ibexa_demo_site' => $config,
                ],
            ]
        );

        foreach ($expected as $key => $val) {
            $this->assertConfigResolverParameterValue($key, $val, 'ibexa_demo_site');
        }
    }

    /**
     * @return array<array{
     *     array{
     *         embedding_models?: array<string, array{
     *             name: string,
     *             dimensions: int,
     *             field_suffix: string,
     *             embedding_provider: string
     *         }>,
     *         default_embedding_model?: string
     *     },
     *     array{
     *         embedding_models: array<string, array{
     *             name: string,
     *             dimensions: int,
     *             field_suffix: string,
     *             embedding_provider: string
     *         }>,
     *         default_embedding_model: string
     *     }
     * }>
     */
    public function embeddingsSettingsProvider(): array
    {
        return [
            [
                [
                    'embedding_models' => [
                        'text-embedding-ada-002' => [
                            'name' => 'text-embedding-ada-002',
                            'dimensions' => 1536,
                            'field_suffix' => 'ada',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                        'text-embedding-3-small' => [
                            'name' => 'text-embedding-3-small',
                            'dimensions' => 1536,
                            'field_suffix' => '3small',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                        'text-embedding-3-large' => [
                            'name' => 'text-embedding-3-large',
                            'dimensions' => 3072,
                            'field_suffix' => '3large',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                    ],
                ],
                [
                    'embedding_models' => [
                        'text-embedding-ada-002' => [
                            'name' => 'text-embedding-ada-002',
                            'dimensions' => 1536,
                            'field_suffix' => 'ada',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                        'text-embedding-3-small' => [
                            'name' => 'text-embedding-3-small',
                            'dimensions' => 1536,
                            'field_suffix' => '3small',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                        'text-embedding-3-large' => [
                            'name' => 'text-embedding-3-large',
                            'dimensions' => 3072,
                            'field_suffix' => '3large',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                    ],
                    'default_embedding_model' => 'text-embedding-ada-002',
                ],
            ],
            [
                [
                    'embedding_models' => [
                        'text-embedding-ada-002' => [
                            'name' => 'text-embedding-ada-002',
                            'dimensions' => 1536,
                            'field_suffix' => 'ada',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                    ],
                    'default_embedding_model' => 'text-embedding-foo',
                ],
                [
                    'embedding_models' => [
                        'text-embedding-ada-002' => [
                            'name' => 'text-embedding-ada-002',
                            'dimensions' => 1536,
                            'field_suffix' => 'ada',
                            'embedding_provider' => 'ibexa_openai',
                        ],
                    ],
                    'default_embedding_model' => 'text-embedding-foo',
                ],
            ],
        ];
    }
}
