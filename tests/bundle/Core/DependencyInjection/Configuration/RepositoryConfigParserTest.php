<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\RepositoryConfigParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\RepositoryConfigParserInterface;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @phpstan-import-type TRootNode from SiteAccessConfiguration
 */
final class RepositoryConfigParserTest extends TestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testAddSemanticConfig(): void
    {
        /** @phpstan-var NodeBuilder<TRootNode> & MockObject $nodeBuilder */
        $nodeBuilder = $this->createMock(NodeBuilder::class);

        $repositoryConfigParser = new RepositoryConfigParser([
            $this->createRepositoryConfigParserMock($nodeBuilder),
            $this->createRepositoryConfigParserMock($nodeBuilder),
            $this->createRepositoryConfigParserMock($nodeBuilder),
        ]);

        $repositoryConfigParser->addSemanticConfig($nodeBuilder);
    }

    /**
     * @phpstan-param NodeBuilder<TRootNode> $nodeBuilder
     */
    private function createRepositoryConfigParserMock(
        NodeBuilder $nodeBuilder
    ): RepositoryConfigParserInterface {
        $configParser = $this->createMock(RepositoryConfigParserInterface::class);
        $configParser
            ->expects(self::once())
            ->method('addSemanticConfig')
            ->with($nodeBuilder);

        return $configParser;
    }
}
