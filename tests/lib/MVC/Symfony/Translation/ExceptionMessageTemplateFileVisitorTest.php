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
use Psr\Log\LoggerInterface;
use SplFileInfo;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Translation\ExceptionMessageTemplateFileVisitor
 */
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
