<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use Ibexa\Core\MVC\Symfony\Translation\ExceptionMessageTemplateFileVisitor;
use JMS\TranslationBundle\Logger\LoggerAwareInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use SplFileInfo;

#[CoversClass(ExceptionMessageTemplateFileVisitor::class)]
final class ExceptionMessageTemplateFileVisitorTest extends BaseMessageExtractorPhpFileVisitorTestCase
{
    public static function getDataForTestExtractTranslation(): iterable
    {
        yield 'TranslatableBase::setMessageTemplate()' => [
            'SetMessageTemplate.php',
            [
                new Message('Foo exception', 'ibexa_repository_exceptions'),
            ],
        ];
    }

    public function testFirstClassCallableIsSkipped(): void
    {
        $messageCatalogue = new MessageCatalogue();

        // first-class callable syntax is parsed by php-parser regardless of the PHP version running the test
        $ast = $this->phpParser->parse(
            '<?php $setMessageTemplate = $this->setMessageTemplate(...); $setMessageTemplate(\'Foo exception\');'
        );
        self::assertNotNull($ast);

        $this->visitor->visitPhpFile(
            new SplFileInfo(__FILE__),
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

        $ast = $this->getASTFromFile($file);

        if ($this->visitor instanceof LoggerAwareInterface) {
            $logger = $this->createMock(LoggerInterface::class);
            $logger
                ->expects(self::once())
                ->method('error');

            $this->visitor->setLogger($logger);
        }

        $this->visitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );
    }

    protected function buildVisitor(DocParser $docParser, FileSourceFactory $fileSourceFactory): FileVisitorInterface
    {
        return new ExceptionMessageTemplateFileVisitor(
            $docParser,
            $fileSourceFactory
        );
    }
}
