<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\URLAliasService;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\URLAliasService;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ContentService::class, 'publishVersion')]
#[CoversMethod(URLAliasService::class, 'createUrlAlias')]
final class CustomUrlAliasForMultilingualContentTest extends BaseTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testCreateCustomUrlAliasWithTheSamePathThrowsException(): void
    {
        $repository = $this->getRepository();
        $urlAliasService = $repository->getURLAliasService();
        $locationService = $repository->getLocationService();
        $language = 'ger-DE';

        $names = [
            'eng-GB' => 'Contact',
            'ger-DE' => 'Kontakt',
            'eng-US' => 'Contact',
        ];
        $contactFolder = $this->createFolder(
            $names,
            2,
            null,
            false // not always available, so the created content behaves the same as "article"
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument \'$path\' is invalid: Path \'Contact\' already exists for the given context'
        );
        // attempt to create custom alias for German translation while a system one
        // for a different translation already exists
        $urlAliasService->createUrlAlias(
            $locationService->loadLocation(
                $contactFolder->contentInfo->mainLocationId
            ),
            'Contact',
            $language,
            true, // forwarding
            true // always available
        );
    }
}
