<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Values\Content;

use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

final class DefaultLanguageCodeForContentTest extends BaseTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testDefaultLanguageCodeForCreatedContentWithoutPrioritizedLanguage(): void
    {
        $names = [
            'eng-GB' => 'Test GB',
            'ger-DE' => 'Test DE',
            'eng-US' => 'Test US',
        ];
        $testFolder = $this->createFolder(
            $names,
            2,
            null,
            false
        );

        self::assertEquals('eng-GB', $testFolder->getDefaultLanguageCode());
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testDefaultLanguageCodeForCreatedContentWithPrioritizedLanguage(): void
    {
        $names = [
            'eng-GB' => 'Test GB',
            'ger-DE' => 'Test DE',
            'eng-US' => 'Test US',
        ];

        $testFolder = $this->createFolder(
            $names,
            2,
            null,
            false
        );

        $repository = $this->getRepository();
        $testFolderInGerman = $repository->getContentService()->loadContent($testFolder->id, ['ger-DE']);

        self::assertEquals('ger-DE', $testFolderInGerman->getDefaultLanguageCode());
    }
}
