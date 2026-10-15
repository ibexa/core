<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

abstract class BaseMessageExtractorPhpFileVisitorTestCase extends TestCase
{
    protected const string FIXTURES_DIR = __DIR__ . '/fixtures/';

    protected Parser $phpParser;

    protected FileVisitorInterface $visitor;

    /**
     * @return iterable<string, array{string, array<Message>}>
     */
    abstract public static function getDataForTestExtractTranslation(): iterable;

    abstract protected function buildVisitor(
        DocParser $docParser,
        FileSourceFactory $fileSourceFactory
    ): FileVisitorInterface;

    protected function setUp(): void
    {
        $docParser = new DocParser();
        $fileSourceFactory = new FileSourceFactory(self::FIXTURES_DIR);
        $factory = new ParserFactory();
        $this->phpParser = $factory->createForHostVersion();
        $this->visitor = $this->buildVisitor($docParser, $fileSourceFactory);
    }

    /**
     * @dataProvider getDataForTestExtractTranslation
     *
     * @param array<Message> $expectedMessages
     */
    public function testExtractTranslation(
        string $phpFileName,
        array $expectedMessages
    ): void {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . $phpFileName;
        $fileInfo = new SplFileInfo($file);

        $ast = $this->getASTFromFile($file);
        $this->visitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );

        foreach ($expectedMessages as $expectedMessage) {
            self::assertTrue(
                $messageCatalogue->has($expectedMessage),
                'Message catalogue does not have the expected message: ' . var_export($expectedMessage, true) . PHP_EOL
                . 'Current message catalogue structure: ' . var_export($messageCatalogue, true)
            );
        }
    }

    public function testNoTranslationToExtract(): void
    {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . 'NoTranslationToExtract.php';
        $fileInfo = new SplFileInfo($file);

        $ast = $this->getASTFromFile($file);
        $this->visitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );

        self::assertEmpty($messageCatalogue->getDomains());
    }

    /**
     * @return Stmt[]
     */
    protected function getASTFromFile(string $filePath): array
    {
        $fileContents = file_get_contents($filePath);
        assert($fileContents !== false, "Failed to read $filePath");

        $ast = $this->phpParser->parse($fileContents);
        assert($ast !== null, "Failed to parse AST of $filePath");

        return $ast;
    }
}
