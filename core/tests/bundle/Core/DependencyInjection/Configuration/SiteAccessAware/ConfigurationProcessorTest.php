<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ConfigurationMapperInterface;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\HookableConfigurationMapperInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationProcessorTest extends TestCase
{
    public function testConstruct()
    {
        $namespace = 'ibexa_test';
        $siteAccessNodeName = 'foo';
        $container = $this->getContainerMock();
        $siteAccessList = ['test', 'bar'];
        $groupsBySa = ['test' => ['group1', 'group2'], 'bar' => ['group1', 'group3']];
        $siteAccessGroups = [
            'group1' => ['test', 'bar'],
            'group2' => ['test'],
            'group3' => ['bar'],
        ];
        ConfigurationProcessor::setAvailableSiteAccesses($siteAccessList);
        ConfigurationProcessor::setGroupsBySiteAccess($groupsBySa);
        ConfigurationProcessor::setAvailableSiteAccessGroups($siteAccessGroups);
        $processor = new ConfigurationProcessor($container, $namespace, $siteAccessNodeName);

        $contextualizer = $processor->getContextualizer();
        self::assertInstanceOf(ContextualizerInterface::class, $contextualizer);
        self::assertSame($container, $contextualizer->getContainer());
        self::assertSame($namespace, $contextualizer->getNamespace());
        self::assertSame($siteAccessNodeName, $contextualizer->getSiteAccessNodeName());
        self::assertSame($siteAccessList, $contextualizer->getAvailableSiteAccesses());
        self::assertSame($groupsBySa, $contextualizer->getGroupsBySiteAccess());
    }

    public function testGetSetContextualizer()
    {
        $namespace = 'ibexa_test';
        $siteAccessNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $siteAccessNodeName);

        self::assertInstanceOf(
            ContextualizerInterface::class,
            $processor->getContextualizer()
        );

        $newContextualizer = $this->getContextualizerMock();
        $processor->setContextualizer($newContextualizer);
        self::assertSame($newContextualizer, $processor->getContextualizer());
    }

    public function testMapConfigWrongMapper()
    {
        $this->expectException(\InvalidArgumentException::class);

        $namespace = 'ibexa_test';
        $siteAccessNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $siteAccessNodeName);

        $processor->mapConfig([], new stdClass());
    }

    public function testMapConfigClosure()
    {
        $namespace = 'ibexa_test';
        $saNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $saNodeName);
        $expectedContextualizer = $processor->getContextualizer();

        $sa1Name = 'sa1';
        $sa2Name = 'sa2';
        $availableSAs = [$sa1Name => true, $sa2Name => true];
        $sa1Config = [
            'foo' => 'bar',
            'hello' => 'world',
            'an_integer' => 123,
            'a_bool' => true,
        ];
        $sa2Config = [
            'foo' => 'bar2',
            'hello' => 'universe',
            'an_integer' => 456,
            'a_bool' => false,
        ];
        $config = [
            'not_sa_aware' => 'blabla',
            $saNodeName => [
                'sa1' => $sa1Config,
                'sa2' => $sa2Config,
            ],
        ];

        $mapperClosure = static function (array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer) use ($config, $availableSAs, $saNodeName, $expectedContextualizer) {
            self::assertTrue(isset($availableSAs[$currentScope]));
            self::assertSame($config[$saNodeName][$currentScope], $scopeSettings);
            self::assertSame($expectedContextualizer, $contextualizer);
        };
        $processor->mapConfig($config, $mapperClosure);
    }

    public function testMapConfigMapperObject()
    {
        $namespace = 'ibexa_test';
        $saNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $saNodeName);
        $contextualizer = $processor->getContextualizer();

        $sa1Name = 'sa1';
        $sa2Name = 'sa2';
        $sa1Config = [
            'foo' => 'bar',
            'hello' => 'world',
            'an_integer' => 123,
            'a_bool' => true,
        ];
        $sa2Config = [
            'foo' => 'bar2',
            'hello' => 'universe',
            'an_integer' => 456,
            'a_bool' => false,
        ];
        $config = [
            'not_sa_aware' => 'blabla',
            $saNodeName => [
                'sa1' => $sa1Config,
                'sa2' => $sa2Config,
            ],
        ];

        $mapper = $this->createMock(ConfigurationMapperInterface::class);
        $mapper
            ->expects(self::exactly(count($config[$saNodeName])))
            ->method('mapConfig')
            ->will(
                self::returnValueMap(
                    [
                        [$sa1Config, $sa1Name, $contextualizer, null],
                        [$sa2Config, $sa2Name, $contextualizer, null],
                    ]
                )
            );

        $processor->mapConfig($config, $mapper);
    }

    public function testMapConfigHookableMapperObject()
    {
        $namespace = 'ibexa_test';
        $saNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $saNodeName);
        $contextualizer = $processor->getContextualizer();

        $sa1Name = 'sa1';
        $sa2Name = 'sa2';
        $sa1Config = [
            'foo' => 'bar',
            'hello' => 'world',
            'an_integer' => 123,
            'a_bool' => true,
        ];
        $sa2Config = [
            'foo' => 'bar2',
            'hello' => 'universe',
            'an_integer' => 456,
            'a_bool' => false,
        ];
        $config = [
            'not_sa_aware' => 'blabla',
            $saNodeName => [
                'sa1' => $sa1Config,
                'sa2' => $sa2Config,
            ],
        ];

        $mapper = $this->createMock(HookableConfigurationMapperInterface::class);
        $mapper
            ->expects(self::once())
            ->method('preMap')
            ->with($config, $contextualizer);
        $mapper
            ->expects(self::once())
            ->method('postMap')
            ->with($config, $contextualizer);
        $mapper
            ->expects(self::exactly(count($config[$saNodeName])))
            ->method('mapConfig')
            ->will(
                self::returnValueMap(
                    [
                        [$sa1Config, $sa1Name, $contextualizer, null],
                        [$sa2Config, $sa2Name, $contextualizer, null],
                    ]
                )
            );

        $processor->mapConfig($config, $mapper);
    }

    public function testMapSetting()
    {
        $namespace = 'ibexa_test';
        $saNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $saNodeName);
        $contextualizer = $this->getContextualizerMock();
        $processor->setContextualizer($contextualizer);

        $sa1Config = [
            'foo' => 'bar',
            'hello' => 'world',
            'an_integer' => 123,
            'a_bool' => true,
        ];
        $sa2Config = [
            'foo' => 'bar2',
            'hello' => 'universe',
            'an_integer' => 456,
            'a_bool' => false,
        ];
        $config = [
            'not_sa_aware' => 'blabla',
            $saNodeName => [
                'sa1' => $sa1Config,
                'sa2' => $sa2Config,
            ],
        ];

        $contextualizer
            ->expects(self::once())
            ->method('mapSetting')
            ->with('foo', $config);
        $processor->mapSetting('foo', $config);
    }

    public function testMapConfigArray()
    {
        $namespace = 'ibexa_test';
        $saNodeName = 'foo';
        $container = $this->getContainerMock();
        $processor = new ConfigurationProcessor($container, $namespace, $saNodeName);
        $contextualizer = $this->getContextualizerMock();
        $processor->setContextualizer($contextualizer);

        $sa1Config = [
            'foo' => 'bar',
            'hello' => ['world'],
            'an_integer' => 123,
            'a_bool' => true,
        ];
        $sa2Config = [
            'foo' => 'bar2',
            'hello' => ['universe'],
            'an_integer' => 456,
            'a_bool' => false,
        ];
        $config = [
            'not_sa_aware' => 'blabla',
            $saNodeName => [
                'sa1' => $sa1Config,
                'sa2' => $sa2Config,
            ],
        ];

        $contextualizer
            ->expects(self::once())
            ->method('mapConfigArray')
            ->with('hello', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL);
        $processor->mapConfigArray('hello', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL);
    }

    protected function getContainerMock()
    {
        return $this->createMock(ContainerInterface::class);
    }

    protected function getContextualizerMock()
    {
        return $this->createMock(ContextualizerInterface::class);
    }
}
