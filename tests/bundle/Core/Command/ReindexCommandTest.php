<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Command;

use Ibexa\Bundle\Core\Command\Indexer\ContentIdListGeneratorStrategyInterface;
use Ibexa\Bundle\Core\Command\ReindexCommand;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Search\Content\IndexerGateway;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Search\Common\IncrementalIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ReindexCommand::class)]
final class ReindexCommandTest extends TestCase
{
    public function testIndexesTheGivenContentIdsAsIntegers(): void
    {
        $indexer = $this->createIndexer();
        $indexer
            ->expects($this->once())
            ->method('updateSearchIndex')
            ->with(self::identicalTo([2, 34, 68]), true);

        self::assertSame(
            Command::SUCCESS,
            $this->createCommandTester($indexer)->execute(['--content-ids' => '2, 34,68'])
        );
    }

    /**
     * @param array<string, string> $options
     */
    #[DataProvider('provideInvalidOptionValues')]
    public function testRejectsAnInvalidOptionValue(
        array $options,
        string $expectedMessage
    ): void {
        $indexer = $this->createIndexer();
        $indexer->expects($this->never())->method('updateSearchIndex');
        $indexer->expects($this->never())->method('purge');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createCommandTester($indexer)->execute($options);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function provideInvalidOptionValues(): iterable
    {
        yield 'content id that is not a number' => [
            ['--content-ids' => '2,abc'],
            "Argument '--content-ids' is invalid: The value must be an integer of at least 1, you provided 'abc'",
        ];
        yield 'content id 0' => [
            ['--content-ids' => '0'],
            "Argument '--content-ids' is invalid: The value must be an integer of at least 1, you provided '0'",
        ];
        yield 'empty content id list' => [
            ['--content-ids' => ''],
            "Argument '--content-ids' is invalid: The value must be an integer of at least 1, you provided ''",
        ];
        yield 'subtree that is not a number' => [
            ['--subtree' => 'abc'],
            "Argument '--subtree' is invalid: The value must be an integer of at least 1, you provided 'abc'",
        ];
        yield 'iteration count 0' => [
            ['--iteration-count' => '0'],
            "Argument '--iteration-count' is invalid: The value must be an integer of at least 1, you provided '0'",
        ];
        yield 'negative number of processes' => [
            ['--processes' => '-1'],
            "Argument '--processes' is invalid: The value must be an integer of at least 0, you provided '-1'",
        ];
    }

    private function createIndexer(): IncrementalIndexer & MockObject
    {
        $indexer = $this->createMock(IncrementalIndexer::class);
        $indexer->method('getName')->willReturn('legacy');

        return $indexer;
    }

    private function createCommandTester(IncrementalIndexer $indexer): CommandTester
    {
        $command = new ReindexCommand(
            $indexer,
            self::createStub(LocationHandler::class),
            self::createStub(IndexerGateway::class),
            new NullLogger(),
            'site',
            'test',
            false,
            sys_get_temp_dir(),
            self::createStub(ContentIdListGeneratorStrategyInterface::class),
            self::createStub(RepositoryConfigurationProviderInterface::class)
        );
        $command->getDefinition()->addOption(new InputOption('siteaccess', null, InputOption::VALUE_REQUIRED));

        return new CommandTester($command);
    }
}
