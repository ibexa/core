<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Main configuration parser/mapper.
 * It acts as a proxy to inner parsers.
 */
class ConfigParser implements ParserInterface
{
    /** @var ParserInterface[] */
    private array $configParsers;

    /**
     * @param ParserInterface[] $configParsers
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $configParsers = [])
    {
        foreach ($configParsers as $parser) {
            if (!$parser instanceof ParserInterface) {
                throw new InvalidArgumentType(
                    'Inner config parser',
                    ParserInterface::class,
                    $parser
                );
            }
        }

        $this->configParsers = $configParsers;
    }

    /**
     * @param ParserInterface[] $configParsers
     */
    public function setConfigParsers(array $configParsers): void
    {
        $this->configParsers = $configParsers;
    }

    /**
     * @return ParserInterface[]
     */
    public function getConfigParsers()
    {
        return $this->configParsers;
    }

    public function mapConfig(
        array &$scopeSettings,
        $currentScope,
        ContextualizerInterface $contextualizer
    ) {
        foreach ($this->configParsers as $parser) {
            $parser->mapConfig($scopeSettings, $currentScope, $contextualizer);
        }
    }

    public function preMap(
        array $config,
        ContextualizerInterface $contextualizer
    ): void {
        foreach ($this->configParsers as $parser) {
            $parser->preMap($config, $contextualizer);
        }
    }

    public function postMap(
        array $config,
        ContextualizerInterface $contextualizer
    ): void {
        foreach ($this->configParsers as $parser) {
            $parser->postMap($config, $contextualizer);
        }
    }

    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $fieldTypeNodeBuilder = $nodeBuilder
            ->arrayNode('fieldtypes')
            ->children();

        // Delegate to configuration parsers
        foreach ($this->configParsers as $parser) {
            if ($parser instanceof FieldTypeParserInterface) {
                $parser->addSemanticConfig($fieldTypeNodeBuilder);
            } else {
                $parser->addSemanticConfig($nodeBuilder);
            }
        }
    }
}
