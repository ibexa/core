<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Content as ContentConfigParser;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\Yaml\Yaml;

class ContentTest extends AbstractParserTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension([new ContentConfigParser()]),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_minimal.yml'));
    }

    public function testDefaultContentSettings()
    {
        $this->load();

        $this->assertConfigResolverParameterValue('content.view_cache', true, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('content.ttl_cache', true, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('content.default_ttl', 60, 'ibexa_demo_site');
    }

    /**
     * @dataProvider contentSettingsProvider
     */
    public function testContentSettings(array $config, array $expected)
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

    public function contentSettingsProvider()
    {
        return [
            [
                [
                    'content' => [
                        'view_cache' => true,
                        'ttl_cache' => true,
                        'default_ttl' => 100,
                    ],
                ],
                [
                    'content.view_cache' => true,
                    'content.ttl_cache' => true,
                    'content.default_ttl' => 100,
                ],
            ],
            [
                [
                    'content' => [
                        'view_cache' => false,
                        'ttl_cache' => false,
                        'default_ttl' => 123,
                    ],
                ],
                [
                    'content.view_cache' => false,
                    'content.ttl_cache' => false,
                    'content.default_ttl' => 123,
                ],
            ],
            [
                [
                    'content' => [
                        'view_cache' => false,
                    ],
                ],
                [
                    'content.view_cache' => false,
                    'content.ttl_cache' => true,
                    'content.default_ttl' => 60,
                ],
            ],
            [
                [
                    'content' => [
                        'tree_root' => ['location_id' => 123],
                    ],
                ],
                [
                    'content.view_cache' => true,
                    'content.ttl_cache' => true,
                    'content.default_ttl' => 60,
                    'content.tree_root.location_id' => 123,
                ],
            ],
            [
                [
                    'content' => [
                        'tree_root' => [
                            'location_id' => 456,
                            'excluded_uri_prefixes' => ['/media/images', '/products'],
                        ],
                    ],
                ],
                [
                    'content.view_cache' => true,
                    'content.ttl_cache' => true,
                    'content.default_ttl' => 60,
                    'content.tree_root.location_id' => 456,
                    'content.tree_root.excluded_uri_prefixes' => ['/media/images', '/products'],
                ],
            ],
        ];
    }
}
