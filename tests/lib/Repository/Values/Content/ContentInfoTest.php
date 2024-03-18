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
        self::assertSame($contentInfo->getId(), 1);
        self::assertSame($contentInfo->contentTypeId, 2);
        self::assertSame($contentInfo->name, 'foo');
        self::assertSame($contentInfo->getSectionId(), 1);
        self::assertSame($contentInfo->currentVersionNo, 1);
        self::assertTrue($contentInfo->isPublished());
        self::assertSame($contentInfo->ownerId, 10);
        self::assertSame($dateFormatted, $contentInfo->modificationDate->format('c'));
        self::assertSame($dateFormatted, $contentInfo->publishedDate->format('c'));
        self::assertFalse($contentInfo->alwaysAvailable);
        self::assertSame($contentInfo->remoteId, '1qaz2wsx');
        self::assertSame($contentInfo->getMainLanguageCode(), 'eng-GB');
        self::assertSame($contentInfo->getMainLocationId(), 2);
    }
}

class_alias(ContentInfoTest::class, 'eZ\Publish\Core\Repository\Tests\Values\Content\ContentInfoTest');
