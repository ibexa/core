<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use Ibexa\Core\MVC\Symfony\Translation\ExceptionMessageTemplateFileVisitor;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SplFileInfo;

final class ExceptionMessageTemplateFileVisitorTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/fixtures/';

    private Parser $phpParser;

    private ExceptionMessageTemplateFileVisitor $exceptionMessageTemplateFileVisitor;

    protected function setUp(): void
    {
        $docParser = new DocParser();
        $fileSourceFactory = new FileSourceFactory(
            self::FIXTURES_DIR,
        );
        $lexer = new Lexer();
        $factory = new ParserFactory();
        $this->phpParser = $factory->create(ParserFactory::PREFER_PHP7, $lexer);
        $this->exceptionMessageTemplateFileVisitor = new ExceptionMessageTemplateFileVisitor(
            $docParser,
            $fileSourceFactory
        );
    }

    public function testExtractTranslation(): void
    {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . 'SetMessageTemplate.php';
        $fileInfo = new SplFileInfo($file);

        $ast = $this->phpParser->parse(file_get_contents($file));
        $this->exceptionMessageTemplateFileVisitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );

        $expectedMessage = new Message('Foo exception', 'ibexa_repository_exceptions');

        self::assertTrue(
            $messageCatalogue->has($expectedMessage)
        );
    }

    public function testNoTranslationToExtract(): void
    {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . 'NoTranslationToExtract.php';
        $fileInfo = new SplFileInfo($file);

        $ast = $this->phpParser->parse(file_get_contents($file));
        $this->exceptionMessageTemplateFileVisitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );

        self::assertEmpty($messageCatalogue->getDomains());
    }

    public function testWrongTranslationId(): void
    {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . 'WrongTranslationId.php';
        $fileInfo = new SplFileInfo($file);

        $ast = $this->phpParser->parse(file_get_contents($file));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error');

        $this->exceptionMessageTemplateFileVisitor->setLogger($logger);
        $this->exceptionMessageTemplateFileVisitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );
    }
}
