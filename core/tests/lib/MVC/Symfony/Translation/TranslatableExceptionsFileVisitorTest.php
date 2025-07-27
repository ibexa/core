<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use Ibexa\Core\MVC\Symfony\Translation\TranslatableExceptionsFileVisitor;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Translation\TranslatableExceptionsFileVisitor
 */
final class TranslatableExceptionsFileVisitorTest extends BaseMessageExtractorPhpFileVisitorTestCase
{
    public static function getDataForTestExtractTranslation(): iterable
    {
        yield 'throw new ContentValidationException()' => [
            'ContentValidationExceptionUsageStub.php',
            [
                new Message('Content with ID %contentId% could not be found', 'ibexa_repository_exceptions'),
            ],
        ];

        yield 'throw new ForbiddenException()' => [
            'ForbiddenExceptionUsageStub.php',
            [
                new Message('Forbidden exception', 'ibexa_repository_exceptions'),
            ],
        ];
    }

    protected function buildVisitor(DocParser $docParser, FileSourceFactory $fileSourceFactory): FileVisitorInterface
    {
        return new TranslatableExceptionsFileVisitor($docParser, $fileSourceFactory);
    }
}
