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
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Translation\ValidationErrorFileVisitor
 */
final class ValidationErrorFileVisitorTest extends BaseMessageExtractorPhpFileVisitorTestCase
{
    /**
     * @return iterable<string, array{string, array<Message>}>
     */
    public static function getDataForTestExtractTranslation(): iterable
    {
        yield 'new ValidationError()' => [
            'ValidationErrorUsageStub.php',
            [
                new Message('error_1.singular_only', 'ibexa_repository_exceptions'),
                new Message('error_2.singular', 'ibexa_repository_exceptions'),
                new Message('error_2.plural', 'ibexa_repository_exceptions'),
            ],
        ];
    }

    protected function buildVisitor(
        DocParser $docParser,
        FileSourceFactory $fileSourceFactory
    ): FileVisitorInterface {
        return new ValidationErrorFileVisitor(
            $docParser,
            $fileSourceFactory
        );
    }
}
