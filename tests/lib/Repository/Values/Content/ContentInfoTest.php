<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Repository\Values\Content;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
 */
class ContentInfoTest extends TestCase
{
    public function testCreateObject(): void
    {
        $dateTime = new DateTimeImmutable();
        $contentInfo = new ContentInfo(
            [
                'id' => 1,
                'contentTypeId' => 2,
                'name' => 'foo',
                'sectionId' => 1,
                'currentVersionNo' => 1,
                'status' => 1,
                'ownerId' => 10,
                'modificationDate' => $dateTime,
                'publishedDate' => $dateTime,
                'alwaysAvailable' => false,
                'remoteId' => '1qaz2wsx',
                'mainLanguageCode' => 'eng-GB',
                'mainLocationId' => 2,
            ]
        );

        $dateFormatted = $dateTime->format('c');
        self::assertSame(1, $contentInfo->getId());
        self::assertSame(2, $contentInfo->contentTypeId);
        self::assertSame('foo', $contentInfo->name);
        self::assertSame(1, $contentInfo->getSectionId());
        self::assertSame(1, $contentInfo->currentVersionNo);
        self::assertTrue($contentInfo->isPublished());
        self::assertSame(10, $contentInfo->ownerId);
        self::assertSame($dateFormatted, $contentInfo->modificationDate->format('c'));
        self::assertSame($dateFormatted, $contentInfo->publishedDate->format('c'));
        self::assertFalse($contentInfo->alwaysAvailable);
        self::assertSame('1qaz2wsx', $contentInfo->remoteId);
        self::assertSame('eng-GB', $contentInfo->getMainLanguageCode());
        self::assertSame(2, $contentInfo->getMainLocationId());
    }
}

class_alias(ContentInfoTest::class, 'eZ\Publish\Core\Repository\Tests\Values\Content\ContentInfoTest');
