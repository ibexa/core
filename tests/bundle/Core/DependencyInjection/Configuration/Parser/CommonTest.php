<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Common;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\Yaml\Yaml;

class CommonTest extends AbstractParserTestCase
{
    private $minimalConfig;

    protected function getContainerExtensions(): array
    {
        return [new IbexaCoreExtension([new Common()])];
    }

    protected function getMinimalConfiguration(): array
    {
        return $this->minimalConfig = Yaml::parse(file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_minimal.yml'));
    }

    public function testIndexPage()
    {
        $indexPage1 = '/Getting-Started';
        $indexPage2 = '/Contact-Us';
        $config = [
            'system' => [
                'ibexa_demo_site' => ['index_page' => $indexPage1],
                'ibexa_demo_site_admin' => ['index_page' => $indexPage2],
            ],
        ];
        $this->load($config);

        $this->assertConfigResolverParameterValue('index_page', $indexPage1, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('index_page', $indexPage2, 'ibexa_demo_site_admin');
        $this->assertConfigResolverParameterValue('index_page', null, self::EMPTY_SA_GROUP);
    }

    public function testDefaultPage()
    {
        $defaultPage1 = '/Getting-Started';
        $defaultPage2 = '/Foo/bar';
        $config = [
            'system' => [
                'ibexa_demo_site' => ['default_page' => $defaultPage1],
                'ibexa_demo_site_admin' => ['default_page' => $defaultPage2],
            ],
        ];
        $this->load($config);

        $this->assertConfigResolverParameterValue('default_page', $defaultPage1, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('default_page', $defaultPage2, 'ibexa_demo_site_admin');
        $this->assertConfigResolverParameterValue('index_page', null, self::EMPTY_SA_GROUP);
    }

    /**
     * Test defaults.
     */
    public function testNonExistentSettings()
    {
        $this->load();
        $this->assertConfigResolverParameterValue('url_alias_router', true, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('cache_service_name', 'cache.app', 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('var_dir', 'var', 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('storage_dir', 'storage', 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('binary_dir', 'original', 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('http_cache.purge_servers', [], 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('anonymous_user_id', 10, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('index_page', null, 'ibexa_demo_site');
    }

    public function testMiscSettings()
    {
        $cachePoolName = 'cache_foo';
        $varDir = 'var/foo/bar';
        $storageDir = 'alternative_storage_folder';
        $binaryDir = 'alternative_binary_folder';
        $sessionName = 'alternative_session_name';
        $indexPage = '/alternative_index_page';
        $cachePurgeServers = [
            'http://purge.server1/',
            'http://purge.server2:1234/foo',
            'https://purge.server3/bar',
        ];
        $anonymousUserId = 10;
        $this->load(
            [
                'system' => [
                    'ibexa_demo_site' => [
                        'cache_service_name' => $cachePoolName,
                        'var_dir' => $varDir,
                        'storage_dir' => $storageDir,
                        'binary_dir' => $binaryDir,
                        'index_page' => $indexPage,
                        'http_cache' => [
                            'purge_servers' => $cachePurgeServers,
                        ],
                        'anonymous_user_id' => $anonymousUserId,
                    ],
                ],
            ]
        );

        $this->assertConfigResolverParameterValue('cache_service_name', $cachePoolName, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('var_dir', $varDir, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('storage_dir', $storageDir, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('binary_dir', $binaryDir, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('index_page', $indexPage, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('http_cache.purge_servers', $cachePurgeServers, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('anonymous_user_id', $anonymousUserId, 'ibexa_demo_site');
    }

    public function testApiKeysSettings()
    {
        $key = 'my_key';
        $this->load(
            [
                'system' => [
                    'ibexa_demo_group' => [
                        'api_keys' => [
                            'google_maps' => $key,
                        ],
                    ],
                ],
            ]
        );

        $this->assertConfigResolverParameterValue('api_keys', ['google_maps' => $key], 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('api_keys.google_maps', $key, 'ibexa_demo_site');
    }

    public function testUserSettings()
    {
        $layout = 'somelayout.html.twig';
        $loginTemplate = 'login_template.html.twig';
        $this->load(
            [
                'system' => [
                    'ibexa_demo_site' => [
                        'user' => [
                            'layout' => $layout,
                            'login_template' => $loginTemplate,
                        ],
                    ],
                ],
            ]
        );

        $this->assertConfigResolverParameterValue('security.base_layout', $layout, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('security.login_template', $loginTemplate, 'ibexa_demo_site');
    }

    public function testNoUserSettings()
    {
        $this->load();
        $this->assertConfigResolverParameterValue(
            'security.base_layout',
            '%ibexa.site_access.config.default.page_layout%',
            'ibexa_demo_site'
        );
        $this->assertConfigResolverParameterValue(
            'security.login_template',
            '@IbexaCore/Security/login.html.twig',
            'ibexa_demo_site'
        );
    }

    /**
     * @dataProvider sessionSettingsProvider
     */
    public function testSessionSettings(
        array $inputParams,
        array $expected
    ) {
        $this->load(
            [
                'system' => [
                    'ibexa_demo_site' => $inputParams,
                ],
            ]
        );

        $this->assertConfigResolverParameterValue('session', $expected['session'], 'ibexa_demo_site');
    }

    public function sessionSettingsProvider()
    {
        return [
            [
                [
                    'session' => [
                        'name' => 'foo',
                        'cookie_path' => '/foo',
                        'cookie_domain' => 'foo.com',
                        'cookie_lifetime' => 86400,
                        'cookie_secure' => false,
                        'cookie_httponly' => true,
                    ],
                ],
                [
                    'session' => [
                        'name' => 'foo',
                        'cookie_path' => '/foo',
                        'cookie_domain' => 'foo.com',
                        'cookie_lifetime' => 86400,
                        'cookie_secure' => false,
                        'cookie_httponly' => true,
                    ],
                ],
            ],
        ];
    }
}
