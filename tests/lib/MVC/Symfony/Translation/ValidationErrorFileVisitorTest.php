<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use Ibexa\Core\MVC\Symfony\Translation\ValidationErrorFileVisitor;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use SplFileInfo;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Translation\ValidationErrorFileVisitor
 */
final class ValidationErrorFileVisitorTest extends BaseMessageExtractorPhpFileVisitorTestCase
{
    /**
     * @return iterable<string, array{string, array<\JMS\TranslationBundle\Model\Message>}>
     */
    public static function getDataForTestExtractTranslation(): iterable
    {
        yield 'new ValidationError()' => [
            'ValidationErrorUsageStub.php',
            [
                new Message('error_1.singular_only', 'ibexa_repository_exceptions'),
                new Message('error_2.singular', 'ibexa_repository_exceptions'),
                new Message('error_2.plural', 'ibexa_repository_exceptions'),
                new Message('error_3.with_desc', 'ibexa_repository_exceptions'),
                new Message('error_4.validators_domain', 'validators'),
            ],
        ];
    }

    public function testExtractTranslationKeepsDescriptionForClassConstant(): void
    {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . 'ValidationErrorUsageStub.php';
        $fileInfo = new SplFileInfo($file);

        $ast = $this->getASTFromFile($file);
        $this->visitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );

        $message = $messageCatalogue->get('error_3.with_desc', 'ibexa_repository_exceptions');

        self::assertNotNull($message);
        self::assertSame('Validation error extracted from class const', $message->getDesc());
    }

    public function testExtractTranslationAllowsDomainOverride(): void
    {
        $messageCatalogue = new MessageCatalogue();
        $file = self::FIXTURES_DIR . 'ValidationErrorUsageStub.php';
        $fileInfo = new SplFileInfo($file);

        $ast = $this->getASTFromFile($file);
        $this->visitor->visitPhpFile(
            $fileInfo,
            $messageCatalogue,
            $ast
        );

        $message = $messageCatalogue->get('error_4.validators_domain', 'validators');

        self::assertNotNull($message);
        self::assertSame('Validation error extracted into validators domain', $message->getDesc());
    }

    protected function buildVisitor(DocParser $docParser, FileSourceFactory $fileSourceFactory): FileVisitorInterface
    {
        return new ValidationErrorFileVisitor(
            $docParser,
            $fileSourceFactory
        );
    }
}
