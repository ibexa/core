<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\URLAliasService;

use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Core\Repository\URLAliasService as CoveredURLAliasService;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CoveredURLAliasService::class)]
final class UrlAliasLookupTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLookup(): void
    {
        $urlAliasService = $this->getIbexaTestCore()->getServiceByClassName(URLAliasService::class);
        $folder = $this->createFolder(['eng-US' => 'Foo']);
        $folderMainLocation = $folder->getVersionInfo()->getContentInfo()->getMainLocation();
        $urlAlias = $urlAliasService->lookup('/Foo');
        self::assertSame(
            $folderMainLocation->id,
            $urlAlias->destination
        );
        $systemUrlAliasList = iterator_to_array($urlAliasService->listLocationAliases($folderMainLocation, false));
        self::assertCount(1, $systemUrlAliasList);
        self::assertEquals($urlAlias, $systemUrlAliasList[0]);
    }
}
