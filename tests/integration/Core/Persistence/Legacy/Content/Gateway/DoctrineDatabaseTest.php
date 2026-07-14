<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Legacy\Content\Gateway;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase;

/**
 * @internal
 *
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase
 */
final class DoctrineDatabaseTest extends IbexaKernelTestCase
{
    private const int CONTENT_ID = 2342;

    private const int VERSION_NO = 1;

    private DoctrineDatabase $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        self::loadSchema();
        self::loadFixtures();

        self::setAdministratorUser();

        $gateway = self::getContainer()->get(DoctrineDatabase::class . '.inner');
        self::assertInstanceOf(DoctrineDatabase::class, $gateway);

        $this->gateway = $gateway;
    }

    public function testInsertVersionThrowsOnDuplicateContentIdAndVersionNo(): void
    {
        $versionInfo = $this->getVersionInfoFixture();

        self::assertGreaterThan(0, $this->gateway->insertVersion($versionInfo, []));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->gateway->insertVersion($versionInfo, []);
    }

    private function getVersionInfoFixture(): VersionInfo
    {
        $versionInfo = new VersionInfo();

        $versionInfo->id = null;
        $versionInfo->versionNo = self::VERSION_NO;
        $versionInfo->creatorId = 14;
        $versionInfo->status = VersionInfo::STATUS_DRAFT;
        $versionInfo->creationDate = 1312278322;
        $versionInfo->modificationDate = 1312278323;
        $versionInfo->initialLanguageCode = 'eng-GB';
        $versionInfo->contentInfo = new ContentInfo(
            [
                'id' => self::CONTENT_ID,
                'alwaysAvailable' => true,
            ]
        );

        return $versionInfo;
    }
}
