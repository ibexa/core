<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Language\Gateway;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends TestCase
{
    /**
     * Database gateway to test.
     */
    protected DoctrineDatabase $databaseGateway;

    /**
     * Inserts DB fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../_fixtures/languages.php'
        );
    }

    public function testInsertLanguage(): void
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->insertLanguage($this->getLanguageFixture());

        self::assertQueryResult(
            [
                [
                    'id' => '8',
                    'locale' => 'de-DE',
                    'name' => 'Deutsch (Deutschland)',
                    'disabled' => '0',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('id', 'locale', 'name', 'disabled')
                ->from('ezcontent_language')
                ->where('id=8')
        );
    }

    /**
     * Returns a Language fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    protected function getLanguageFixture(): Language
    {
        $language = new Language();

        $language->languageCode = 'de-DE';
        $language->name = 'Deutsch (Deutschland)';
        $language->isEnabled = true;

        return $language;
    }

    public function testUpdateLanguage(): void
    {
        $gateway = $this->getDatabaseGateway();

        $language = $this->getLanguageFixture();
        $language->id = 2;

        $gateway->updateLanguage($language);

        self::assertQueryResult(
            [
                [
                    'id' => '2',
                    'locale' => 'de-DE',
                    'name' => 'Deutsch (Deutschland)',
                    'disabled' => '0',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('id', 'locale', 'name', 'disabled')
                ->from('ezcontent_language')
                ->where('id=2')
        );
    }

    public function testLoadLanguageListData(): void
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadLanguageListData([2]);

        self::assertEquals(
            [
                [
                    'id' => '2',
                    'locale' => 'eng-US',
                    'name' => 'English (American)',
                    'disabled' => '0',
                ],
            ],
            $result
        );
    }

    public function testLoadAllLanguagesData(): void
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadAllLanguagesData();

        self::assertEquals(
            [
                [
                    'id' => '2',
                    'locale' => 'eng-US',
                    'name' => 'English (American)',
                    'disabled' => '0',
                ],
                [
                    'id' => '4',
                    'locale' => 'eng-GB',
                    'name' => 'English (United Kingdom)',
                    'disabled' => '0',
                ],
            ],
            $result
        );
    }

    public function testDeleteLanguage(): void
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->deleteLanguage(2);

        self::assertQueryResult(
            [
                [
                    'count' => '1',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * ) AS count')
                ->from('ezcontent_language')
        );

        self::assertQueryResult(
            [
                [
                    'count' => '0',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * ) AS count')
                ->from('ezcontent_language')
                ->where('id=2')
        );
    }

    /**
     * Return a ready to test DoctrineDatabase gateway.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getDatabaseGateway(): DoctrineDatabase
    {
        if (!isset($this->databaseGateway)) {
            $this->databaseGateway = new DoctrineDatabase(
                $this->getDatabaseConnection()
            );
        }

        return $this->databaseGateway;
    }
}
