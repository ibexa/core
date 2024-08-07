<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\URLAliasService;

use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\URLAliasService
 */
final class UrlAliasLookupTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLookup(): void
    {
        $urlAliasService = self::getUrlAliasService();
        $folder = $this->createFolder(['eng-GB' => 'Foo']);
        $folderMainLocation = $folder->getVersionInfo()->getContentInfo()->getMainLocation();
        $urlAlias = $urlAliasService->lookup('/Foo');
        self::assertSame(
            $folderMainLocation->id,
            $urlAlias->destination
        );
        $systemUrlAliasList = $urlAliasService->listLocationAliases($folderMainLocation, false);
        self::assertCount(1, $systemUrlAliasList);
        self::assertEquals($urlAlias, $systemUrlAliasList[0]);
    }
}
