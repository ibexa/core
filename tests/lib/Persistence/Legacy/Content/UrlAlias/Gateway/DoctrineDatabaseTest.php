<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\UrlAlias\Gateway;

use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase as LanguageGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Language\Mapper as LanguageMapper;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator as LanguageMaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway\DoctrineDatabase
 *
 * @group urlalias-gateway
 */
class DoctrineDatabaseTest extends TestCase
{
    protected DoctrineDatabase $gateway;

    /**
     * Test for the loadUrlAliasData() method.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadUrlAliasDataNonExistent(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_simple.php');
        $gateway = $this->getGateway();

        $rows = $gateway->loadUrlAliasData([md5('tri')]);

        self::assertEmpty($rows);
    }

    /**
     * Test for the loadUrlAliasData() method.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadUrlAliasData(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_simple.php');
        $gateway = $this->getGateway();

        $row = $gateway->loadUrlAliasData([md5('jedan'), md5('dva')]);

        self::assertEquals(
            [
                'ezurlalias_ml0_id' => '2',
                'ezurlalias_ml0_link' => '2',
                'ezurlalias_ml0_is_alias' => '0',
                'ezurlalias_ml0_alias_redirects' => '1',
                'ezurlalias_ml0_is_original' => '1',
                'ezurlalias_ml0_action' => 'eznode:314',
                'ezurlalias_ml0_action_type' => 'eznode',
                'ezurlalias_ml0_lang_mask' => '2',
                'ezurlalias_ml0_text' => 'jedan',
                'ezurlalias_ml0_parent' => '0',
                'ezurlalias_ml0_text_md5' => '6896260129051a949051c3847c34466f',
                'id' => '3',
                'link' => '3',
                'is_alias' => '0',
                'alias_redirects' => '1',
                'is_original' => '1',
                'action' => 'eznode:315',
                'action_type' => 'eznode',
                'lang_mask' => '3',
                'text' => 'dva',
                'parent' => '2',
                'text_md5' => 'c67ed9a09ab136fae610b6a087d82e21',
            ],
            $row
        );
    }

    /**
     * Test for the loadUrlAliasData() method.
     *
     * Test with fixture containing language mask with multiple languages.
     */
    public function testLoadUrlAliasDataMultipleLanguages(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_multilang.php');
        $gateway = $this->getGateway();

        $row = $gateway->loadUrlAliasData([md5('jedan'), md5('dva')]);

        self::assertEquals(
            [
                'ezurlalias_ml0_id' => '2',
                'ezurlalias_ml0_link' => '2',
                'ezurlalias_ml0_is_alias' => '0',
                'ezurlalias_ml0_alias_redirects' => '1',
                'ezurlalias_ml0_is_original' => '1',
                'ezurlalias_ml0_action' => 'eznode:314',
                'ezurlalias_ml0_action_type' => 'eznode',
                'ezurlalias_ml0_lang_mask' => '3',
                'ezurlalias_ml0_text' => 'jedan',
                'ezurlalias_ml0_parent' => '0',
                'ezurlalias_ml0_text_md5' => '6896260129051a949051c3847c34466f',
                'id' => '3',
                'link' => '3',
                'is_alias' => '0',
                'alias_redirects' => '1',
                'is_original' => '1',
                'action' => 'eznode:315',
                'action_type' => 'eznode',
                'lang_mask' => '6',
                'text' => 'dva',
                'parent' => '2',
                'text_md5' => 'c67ed9a09ab136fae610b6a087d82e21',
            ],
            $row
        );
    }

    /**
     * @phpstan-return iterable<array{int, list<list<array<string,mixed>>>}>
     */
    public function providerForTestLoadPathData(): iterable
    {
        yield [
            2,
            [
                [
                    ['parent' => '0', 'lang_mask' => '3', 'text' => 'jedan'],
                ],
            ],
        ];
        yield [
            3,
            [
                [
                    ['parent' => '0', 'lang_mask' => '3', 'text' => 'jedan'],
                ],
                [
                    ['parent' => '2', 'lang_mask' => '5', 'text' => 'two'],
                    ['parent' => '2', 'lang_mask' => '3', 'text' => 'dva'],
                ],
            ],
        ];
        yield [
            4,
            [
                [
                    ['parent' => '0', 'lang_mask' => '3', 'text' => 'jedan'],
                ],
                [
                    ['parent' => '2', 'lang_mask' => '5', 'text' => 'two'],
                    ['parent' => '2', 'lang_mask' => '3', 'text' => 'dva'],
                ],
                [
                    ['parent' => '3', 'lang_mask' => '9', 'text' => 'drei'],
                    ['parent' => '3', 'lang_mask' => '5', 'text' => 'three'],
                    ['parent' => '3', 'lang_mask' => '3', 'text' => 'tri'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestLoadPathData
     *
     * @phpstan-param list<list<array<string,mixed>>> $pathData
     */
    public function testLoadPathData(int $id, array $pathData): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_fallback.php');
        $gateway = $this->getGateway();

        $loadedPathData = $gateway->loadPathData($id);

        self::assertEquals(
            $pathData,
            $loadedPathData
        );
    }

    /**
     * @phpstan-return iterable<array{int, list<list<array<string,mixed>>>}>
     */
    public static function providerForTestLoadPathDataMultipleLanguages(): iterable
    {
        yield [
            2,
            [
                [
                    ['parent' => '0', 'lang_mask' => '3', 'text' => 'jedan'],
                ],
            ],
        ];

        yield [
            3,
            [
                [
                    ['parent' => '0', 'lang_mask' => '3', 'text' => 'jedan'],
                ],
                [
                    ['parent' => '2', 'lang_mask' => '6', 'text' => 'dva'],
                ],
            ],
        ];

        yield [
            4,
            [
                [
                    ['parent' => '0', 'lang_mask' => '3', 'text' => 'jedan'],
                ],
                [
                    ['parent' => '2', 'lang_mask' => '6', 'text' => 'dva'],
                ],
                [
                    ['parent' => '3', 'lang_mask' => '4', 'text' => 'three'],
                    ['parent' => '3', 'lang_mask' => '2', 'text' => 'tri'],
                ],
            ],
        ];
    }

    /**
     * Test for the loadPathData() method.
     *
     * @phpstan-param list<list<array<string,mixed>>> $pathData
     *
     * @dataProvider providerForTestLoadPathDataMultipleLanguages
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testLoadPathDataMultipleLanguages(int $id, array $pathData): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_multilang.php');
        $gateway = $this->getGateway();

        $loadedPathData = $gateway->loadPathData($id);

        self::assertEquals(
            $pathData,
            $loadedPathData
        );
    }

    /**
     * @return array
     */
    public function providerForTestCleanupAfterPublishHistorize(): array
    {
        return [
            [
                'action' => 'eznode:314',
                'languageId' => 2,
                'parentId' => 0,
                'textMD5' => '6896260129051a949051c3847c34466f',
            ],
            [
                'action' => 'eznode:315',
                'languageId' => 2,
                'parentId' => 0,
                'textMD5' => 'c67ed9a09ab136fae610b6a087d82e21',
            ],
        ];
    }

    /**
     * Data provider for testArchiveUrlAliasesForDeletedTranslations.
     *
     * @see testArchiveUrlAliasesForDeletedTranslations
     *
     * @return array
     */
    public function providerForTestArchiveUrlAliasesForDeletedTranslations(): array
    {
        return [
            [314, [2]],
            [315, [4]],
            [316, [4]],
            [317, [2, 8]],
            [318, [2, 8]],
        ];
    }

    /**
     * Test for the cleanupAfterPublish() method.
     *
     *
     *
     * @dataProvider providerForTestCleanupAfterPublishHistorize
     */
    public function testCleanupAfterPublishHistorize(string $action, int $languageId, int $parentId, string $textMD5): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_downgrade.php');
        $gateway = $this->getGateway();

        $loadedRow = $gateway->loadRow($parentId, $textMD5);

        $gateway->cleanupAfterPublish($action, $languageId, 42, $parentId, 'jabberwocky');

        $reloadedRow = $gateway->loadRow($parentId, $textMD5);
        $loadedRow['is_original'] = '0';
        $loadedRow['link'] = 42;
        $loadedRow['id'] = 6;

        self::assertEquals($reloadedRow, $loadedRow);
    }

    /**
     * @return array
     */
    public function providerForTestCleanupAfterPublishRemovesLanguage(): array
    {
        return [
            [
                'action' => 'eznode:316',
                'languageId' => 2,
                'parentId' => 0,
                'textMD5' => 'd2cfe69af2d64330670e08efb2c86df7',
            ],
            [
                'action' => 'eznode:317',
                'languageId' => 2,
                'parentId' => 0,
                'textMD5' => '538dca05643d220317ad233cd7be7a0a',
            ],
        ];
    }

    /**
     * Test for the cleanupAfterPublish() method.
     *
     *
     *
     * @dataProvider providerForTestCleanupAfterPublishRemovesLanguage
     */
    public function testCleanupAfterPublishRemovesLanguage(string $action, int $languageId, int $parentId, string $textMD5): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_downgrade.php');
        $gateway = $this->getGateway();

        $loadedRow = $gateway->loadRow($parentId, $textMD5);

        $gateway->cleanupAfterPublish($action, $languageId, 42, $parentId, 'jabberwocky');

        $reloadedRow = $gateway->loadRow($parentId, $textMD5);
        $loadedRow['lang_mask'] = $loadedRow['lang_mask'] & ~$languageId;

        self::assertEquals($reloadedRow, $loadedRow);
    }

    /**
     * Test for the reparent() method.
     *
     * @todo document
     */
    public function testReparent(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_simple.php');
        $gateway = $this->getGateway();

        $gateway->reparent(2, 42);

        self::assertEquals(
            [
                'action' => 'eznode:315',
                'action_type' => 'eznode',
                'alias_redirects' => '1',
                'id' => '3',
                'is_alias' => '0',
                'is_original' => '1',
                'lang_mask' => '3',
                'link' => '3',
                'parent' => '42',
                'text' => 'dva',
                'text_md5' => 'c67ed9a09ab136fae610b6a087d82e21',
            ],
            $gateway->loadRow(42, 'c67ed9a09ab136fae610b6a087d82e21')
        );
    }

    /**
     * Test for the remove() method.
     */
    public function testRemove(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_remove.php');
        $gateway = $this->getGateway();

        $gateway->remove('eznode:314');

        self::assertEmpty($gateway->loadRow(0, 'd5189de027922f81005951e6efe0efd5'));
        self::assertEmpty($gateway->loadRow(0, 'a59d9f07e3d5fcf77911155650956a73'));
        self::assertEmpty($gateway->loadRow(0, '6449cba11bb134a57af94c8cb7f6c99c'));
        self::assertNotEmpty($gateway->loadRow(0, '0a06c09b6dd9a4606b4eb6d60ab188f0'));
        self::assertNotEmpty($gateway->loadRow(0, '82f2bce3283a0806a398fe78beda17d9'));
        self::assertNotEmpty($gateway->loadRow(0, '863d659d9fec68e5ab117b5f585a4ee7'));
    }

    /**
     * Test for the remove() method.
     */
    public function testRemoveWithId(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_remove.php');
        $gateway = $this->getGateway();

        $gateway->remove('eznode:315', 6);

        self::assertEmpty($gateway->loadRow(0, '0a06c09b6dd9a4606b4eb6d60ab188f0'));
        self::assertEmpty($gateway->loadRow(0, '82f2bce3283a0806a398fe78beda17d9'));
        self::assertNotEmpty($gateway->loadRow(0, '863d659d9fec68e5ab117b5f585a4ee7'));
        self::assertNotEmpty($gateway->loadRow(0, 'd5189de027922f81005951e6efe0efd5'));
        self::assertNotEmpty($gateway->loadRow(0, 'a59d9f07e3d5fcf77911155650956a73'));
        self::assertNotEmpty($gateway->loadRow(0, '6449cba11bb134a57af94c8cb7f6c99c'));
    }

    /**
     * Test for the removeCustomAlias() method.
     */
    public function testRemoveCustomAlias(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_remove.php');
        $gateway = $this->getGateway();

        $result = $gateway->removeCustomAlias(0, '6449cba11bb134a57af94c8cb7f6c99c');

        self::assertTrue($result);
        self::assertNotEmpty($gateway->loadRow(0, 'd5189de027922f81005951e6efe0efd5'));
        self::assertNotEmpty($gateway->loadRow(0, 'a59d9f07e3d5fcf77911155650956a73'));
        self::assertEmpty($gateway->loadRow(0, '6449cba11bb134a57af94c8cb7f6c99c'));
    }

    /**
     * Test for the removeByAction() method.
     */
    public function testRemoveCustomAliasFails(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_remove.php');
        $gateway = $this->getGateway();

        $result = $gateway->removeCustomAlias(0, 'd5189de027922f81005951e6efe0efd5');

        self::assertFalse($result);
        self::assertNotEmpty($gateway->loadRow(0, 'd5189de027922f81005951e6efe0efd5'));
    }

    /**
     * Test for the getNextId() method.
     */
    public function testGetNextId(): void
    {
        $gateway = $this->getGateway();

        self::assertEquals(1, $gateway->getNextId());
        self::assertEquals(2, $gateway->getNextId());
    }

    /**
     * @dataProvider providerForTestArchiveUrlAliasesForDeletedTranslations
     *
     * @param int $locationId
     * @param int[] $removedLanguageIds
     */
    public function testArchiveUrlAliasesForDeletedTranslations(int $locationId, array $removedLanguageIds): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlaliases_multilang.php');
        $gateway = $this->getGateway();

        foreach ($gateway->loadLocationEntries($locationId) as $row) {
            $gateway->archiveUrlAliasesForDeletedTranslations(
                $locationId,
                (int) $row['parent'],
                $removedLanguageIds
            );
        }

        // check results
        $languageMask = 0;
        foreach ($removedLanguageIds as $languageId) {
            $languageMask |= $languageId;
        }
        foreach ($gateway->loadLocationEntries($locationId) as $row) {
            self::assertNotEquals(0, (int) $row['lang_mask']);
            self::assertNotEquals(1, (int) $row['lang_mask']);
            self::assertEquals(0, (int) $row['lang_mask'] & $languageMask);
        }
    }

    /**
     * Return the DoctrineDatabase gateway implementation to test.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getGateway(): DoctrineDatabase
    {
        if (!isset($this->gateway)) {
            $languageHandler = new LanguageHandler(
                new LanguageGateway($this->getDatabaseConnection()),
                new LanguageMapper()
            );
            $this->gateway = new DoctrineDatabase(
                $this->getDatabaseConnection(),
                new LanguageMaskGenerator($languageHandler)
            );
        }

        return $this->gateway;
    }
}
