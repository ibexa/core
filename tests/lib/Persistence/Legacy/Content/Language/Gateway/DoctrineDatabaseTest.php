<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Language\Gateway;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends TestCase
{
    /**
     * Database gateway to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase
     */
    protected $databaseGateway;

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

    public function testInsertLanguage()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->insertLanguage($this->getLanguageFixture());

        $this->assertQueryResult(
            [
                [
                    'id' => '5',
                    'locale' => 'de-DE',
                    'name' => 'Deutsch (Deutschland)',
                    'disabled' => '0',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('id', 'locale', 'name', 'disabled')
                ->from(Gateway::CONTENT_LANGUAGE_TABLE)
                ->where('id=5')
        );
    }

    /**
     * Returns a Language fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    protected function getLanguageFixture()
    {
        $language = new Language();

        $language->languageCode = 'de-DE';
        $language->name = 'Deutsch (Deutschland)';
        $language->isEnabled = true;

        return $language;
    }

    public function testUpdateLanguage()
    {
        $gateway = $this->getDatabaseGateway();

        $language = $this->getLanguageFixture();
        $language->id = 2;

        $gateway->updateLanguage($language);

        $this->assertQueryResult(
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
                ->from(Gateway::CONTENT_LANGUAGE_TABLE)
                ->where('id=2')
        );
    }

    public function testLoadLanguageListData()
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

    public function testLoadAllLanguagesData()
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

    public function testDeleteLanguage()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->deleteLanguage(2);

        $this->assertQueryResult(
            [
                [
                    'count' => '1',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * ) AS count')
                ->from(Gateway::CONTENT_LANGUAGE_TABLE)
        );

        $this->assertQueryResult(
            [
                [
                    'count' => '0',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * ) AS count')
                ->from(Gateway::CONTENT_LANGUAGE_TABLE)
                ->where('id=2')
        );
    }

    public function testCanDeleteUnusedLanguageAdjacentToAnotherInUseLanguage(): void
    {
        $gateway = $this->getDatabaseGateway();
        $connection = $this->getDatabaseConnection();

        // Both real, independently-allocated languages (post-migration sequential ids commonly end
        // up adjacent like this) - 65 is in use, 64 is not, and must stay deletable regardless.
        $connection->insert(
            Gateway::CONTENT_LANGUAGE_TABLE,
            ['id' => 64, 'locale' => 'fr-FR', 'name' => 'Francais (France)', 'disabled' => 0]
        );
        $connection->insert(
            Gateway::CONTENT_LANGUAGE_TABLE,
            ['id' => 65, 'locale' => 'it-IT', 'name' => 'Italiano (Italia)', 'disabled' => 0]
        );
        $connection->insert(
            'ibexa_object_state_language',
            ['contentobject_state_id' => 1, 'language_id' => 65, 'description' => '', 'name' => 'x']
        );

        self::assertTrue($gateway->canDeleteLanguage(64));
        self::assertFalse($gateway->canDeleteLanguage(65));
    }

    public function testCanDeleteLanguageDetectsLegacyAlwaysAvailableTaintedValue(): void
    {
        $gateway = $this->getDatabaseGateway();
        $connection = $this->getDatabaseConnection();

        // Only language 64 is real; no language 65 exists. A row carrying value 65 in an indicator
        // column can only be legacy data where 64's always-available bit was folded in (64|1 = 65),
        // so it must still count as "language 64 is in use".
        $connection->insert(
            Gateway::CONTENT_LANGUAGE_TABLE,
            ['id' => 64, 'locale' => 'fr-FR', 'name' => 'Francais (France)', 'disabled' => 0]
        );
        $connection->insert(
            'ibexa_object_state_language',
            ['contentobject_state_id' => 1, 'language_id' => 65, 'description' => '', 'name' => 'x']
        );

        self::assertFalse($gateway->canDeleteLanguage(64));
    }

    /**
     * Return a ready to test DoctrineDatabase gateway.
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
