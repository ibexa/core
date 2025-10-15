<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\ValueObject;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

final class ContentInfoTest extends TestCase
{
    public function testGetContentTypeIdentifier(): void
    {
        $contentTypeIdentifier = 'foo';
        $contentInfo = new ContentInfo(['contentTypeIdentifier' => $contentTypeIdentifier]);

        self::assertSame($contentTypeIdentifier, $contentInfo->getContentTypeIdentifier());
    }
}
