<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\ParserInterface;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ConfigParserTest extends TestCase
{
    public function testConstructWrongInnerParser(): void
    {
        $this->expectException(InvalidArgumentType::class);

        new ConfigParser(
            [
                $this->getConfigurationParserMock(),
                new stdClass(),
            ]
        );
    }

    public function testConstruct(): void
    {
        $innerParsers = [
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
        ];
        $configParser = new ConfigParser($innerParsers);
        self::assertSame($innerParsers, $configParser->getConfigParsers());
    }

    public function testGetSetInnerParsers(): void
    {
        $configParser = new ConfigParser();
        self::assertSame([], $configParser->getConfigParsers());

        $innerParsers = [
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
        ];
        $configParser->setConfigParsers($innerParsers);
        self::assertSame($innerParsers, $configParser->getConfigParsers());
    }

    public function testMapConfig(): void
    {
        $parsers = [
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
        ];
        $configParser = new ConfigParser($parsers);

        $scopeSettings = [
            'foo' => 'bar',
            'some' => 'thing',
        ];
        $currentScope = 'the_current_scope';
        $contextualizer = $this->createMock(ContextualizerInterface::class);

        foreach ($parsers as $parser) {
            /* @var \PHPUnit\Framework\MockObject\MockObject $parser */
            $parser
                ->expects(self::once())
                ->method('mapConfig')
                ->with($scopeSettings, $currentScope, $contextualizer);
        }

        $configParser->mapConfig($scopeSettings, $currentScope, $contextualizer);
    }

    public function testPrePostMap(): void
    {
        $parsers = [
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
        ];
        $configParser = new ConfigParser($parsers);

        $config = [
            'foo' => 'bar',
            'some' => 'thing',
        ];
        $contextualizer = $this->createMock(ContextualizerInterface::class);

        foreach ($parsers as $parser) {
            /* @var \PHPUnit\Framework\MockObject\MockObject $parser */
            $parser
                ->expects(self::once())
                ->method('preMap')
                ->with($config, $contextualizer);
            $parser
                ->expects(self::once())
                ->method('postMap')
                ->with($config, $contextualizer);
        }

        $configParser->preMap($config, $contextualizer);
        $configParser->postMap($config, $contextualizer);
    }

    public function testAddSemanticConfig(): void
    {
        $parsers = [
            $this->getConfigurationParserMock(),
            $this->getConfigurationParserMock(),
        ];
        $configParser = new ConfigParser($parsers);

        $nodeBuilder = new NodeBuilder();

        foreach ($parsers as $parser) {
            /* @var \PHPUnit\Framework\MockObject\MockObject $parser */
            $parser
                ->expects(self::once())
                ->method('addSemanticConfig')
                ->with($nodeBuilder);
        }

        $configParser->addSemanticConfig($nodeBuilder);
    }

    protected function getConfigurationParserMock(): MockObject
    {
        return $this->createMock(ParserInterface::class);
    }
}
