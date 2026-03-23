<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;

/**
 * Test case for operations in the LanguageService using in memory storage.
 *
 * @covers \Ibexa\Contracts\Core\Repository\LanguageService
 *
 * @group integration
 * @group language
 */
final class LanguageServiceTest extends IbexaKernelTestCase
{
    use AssertPropertiesTrait;

    private Repository $repository;

    private LanguageService $languageService;

    protected function setUp(): void
    {
        self::bootKernel();

        self::loadSchema();
        self::loadFixtures();

        self::setAdministratorUser();

        $this->repository = self::getServiceByClassName(Repository::class);
        $this->languageService = $this->repository->getContentLanguageService();
    }

    private function generateId(string $type, int $rawId): int
    {
        return self::getServiceByClassName(IdManager::class)->generateId($type, $rawId);
    }

    public function testNewLanguageCreateStruct(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();

        $this->assertPropertiesCorrect(
            [
                'languageCode' => null,
                'name' => null,
                'enabled' => true,
            ],
            $languageCreate
        );
    }

    /**
     * @depends testNewLanguageCreateStruct
     */
    public function testCreateLanguage(): Language
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English (New Zealand)';
        $languageCreate->languageCode = 'eng-NZ';

        $language = $this->languageService->createLanguage($languageCreate);

        $this->expectNotToPerformAssertions();

        return $language;
    }

    /**
     * @depends testCreateLanguage
     */
    public function testCreateLanguageSetsIdPropertyOnReturnedLanguage(Language $language): void
    {
        self::assertNotNull($language->id);
    }

    /**
     * @depends testCreateLanguage
     */
    public function testCreateLanguageSetsExpectedProperties(Language $language): void
    {
        self::assertEquals(
            [
                true,
                'English (New Zealand)',
                'eng-NZ',
            ],
            [
                $language->enabled,
                $language->name,
                $language->languageCode,
            ]
        );
    }

    /**
     * @depends testCreateLanguage
     */
    public function testCreateLanguageThrowsInvalidArgumentException(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'Norwegian';
        $languageCreate->languageCode = 'nor-NO';

        $this->languageService->createLanguage($languageCreate);

        // This call should fail with an InvalidArgumentException, because
        // the language code "nor-NO" already exists.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'languageCreateStruct\' is invalid: language with the "nor-NO" language code already exists');
        $this->languageService->createLanguage($languageCreate);
    }

    public function testCreateLanguageWithEmptyLanguageCode(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = '';

        $this->expectException(InvalidArgumentException::class);
        $this->languageService->createLanguage($languageCreate);
    }

    /**
     * @testWith ["."]
     *           ["ąę"]
     *           ["%^"]
     */
    public function testCreateLanguageWithInvalidLanguageCode(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng 123';

        $this->expectException(InvalidArgumentException::class);
        $this->languageService->createLanguage($languageCreate);
    }

    /**
     * @depends testCreateLanguage
     */
    public function testLoadLanguageById(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = false;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $languageId = $this->languageService->createLanguage($languageCreate)->id;

        $this->languageService->loadLanguageById($languageId);

        $languages = iterator_to_array($this->languageService->loadLanguageListById([$languageId]));

        self::assertIsIterable($languages);
        self::assertCount(1, $languages);
        self::assertInstanceOf(Language::class, $languages[$languageId]);
    }

    /**
     * @depends testLoadLanguageById
     */
    public function testLoadLanguageByIdThrowsNotFoundException(): void
    {
        $nonExistentLanguageId = $this->generateId('language', 2342);

        $languages = $this->languageService->loadLanguageListById([$nonExistentLanguageId]);

        self::assertIsIterable($languages);
        self::assertCount(0, $languages);

        $this->expectException(NotFoundException::class);

        $this->languageService->loadLanguageById($nonExistentLanguageId);
    }

    /**
     * @depends testLoadLanguageById
     */
    public function testUpdateLanguageName(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = false;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $languageId = $this->languageService->createLanguage($languageCreate)->id;

        $language = $this->languageService->loadLanguageById($languageId);

        $this->languageService->updateLanguageName(
            $language,
            'New language name.'
        );

        // Verify that the service also persists the changes
        $updatedLanguage = $this->languageService->loadLanguageById($languageId);
        $this->assertPropertiesCorrect(
            [
                'id' => $language->id,
                'name' => 'New language name.',
                'languageCode' => $language->languageCode,
                'enabled' => $language->enabled,
            ],
            $updatedLanguage
        );
    }

    public function testUpdateLanguageNameThrowsInvalidArgumentException(): void
    {
        $language = $this->languageService->loadLanguage('eng-GB');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'newName\' is invalid: \'\' is incorrect value');
        $this->languageService->updateLanguageName($language, '');
    }

    /**
     * @depends testLoadLanguageById
     */
    public function testEnableLanguage(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = false;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $language = $this->languageService->createLanguage($languageCreate);

        // Now lets enable the newly created language
        $this->languageService->enableLanguage($language);

        $enabledLanguage = $this->languageService->loadLanguageById($language->id);

        self::assertTrue($enabledLanguage->enabled);
    }

    /**
     * @depends testLoadLanguageById
     */
    public function testDisableLanguage(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $language = $this->languageService->createLanguage($languageCreate);

        // Now lets disable the newly created language
        $this->languageService->disableLanguage($language);

        $enabledLanguage = $this->languageService->loadLanguageById($language->id);

        self::assertFalse($enabledLanguage->enabled);
    }

    /**
     * @depends testCreateLanguage
     */
    public function testLoadLanguage(): void
    {
        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $languageId = $this->languageService->createLanguage($languageCreate)->id;

        $language = $this->languageService->loadLanguage('eng-NZ');

        $this->assertPropertiesCorrect(
            [
                'id' => $languageId,
                'languageCode' => 'eng-NZ',
                'name' => 'English',
                'enabled' => true,
            ],
            $language
        );

        $languages = iterator_to_array($this->languageService->loadLanguageListByCode(['eng-NZ']));

        self::assertIsIterable($languages);
        self::assertCount(1, $languages);

        $this->assertPropertiesCorrect(
            [
                'id' => $languageId,
                'languageCode' => 'eng-NZ',
                'name' => 'English',
                'enabled' => true,
            ],
            $languages['eng-NZ']
        );
    }

    /**
     * @depends testLoadLanguage
     */
    public function testLoadLanguageThrowsNotFoundException(): void
    {
        $languages = $this->languageService->loadLanguageListByCode(['fre-FR']);

        self::assertIsIterable($languages);
        self::assertCount(0, $languages);

        $this->expectException(NotFoundException::class);

        $this->languageService->loadLanguage('fre-FR');
    }

    public function testLoadLanguageThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'languageCode\' is invalid: language code has an invalid value');

        $this->languageService->loadLanguage('');
    }

    /**
     * @depends testCreateLanguage
     * @depends testLoadLanguage
     */
    public function testLoadLanguages(): void
    {
        // Create some languages
        $languageCreateEnglish = $this->languageService->newLanguageCreateStruct();
        $languageCreateEnglish->enabled = false;
        $languageCreateEnglish->name = 'English';
        $languageCreateEnglish->languageCode = 'eng-NZ';

        $languageCreateFrench = $this->languageService->newLanguageCreateStruct();
        $languageCreateFrench->enabled = false;
        $languageCreateFrench->name = 'French';
        $languageCreateFrench->languageCode = 'fre-FR';

        $this->languageService->createLanguage($languageCreateEnglish);
        $this->languageService->createLanguage($languageCreateFrench);

        $languages = $this->languageService->loadLanguages();
        self::assertIsArray($languages);
        foreach ($languages as $language) {
            self::assertInstanceOf(Language::class, $language);
            $singleLanguage = $this->languageService->loadLanguage($language->languageCode);
            $this->assertStructPropertiesCorrect(
                $singleLanguage,
                $language,
                ['id', 'languageCode', 'name', 'enabled']
            );
        }

        // eng-US, eng-GB, ger-DE + 2 newly created
        self::assertCount(5, $languages);
    }

    /**
     * @depends testCreateLanguage
     */
    public function loadLanguagesReturnsAnEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->languageService->loadLanguages());
    }

    /**
     * @depends testLoadLanguages
     */
    public function testDeleteLanguage(): void
    {
        $beforeCount = count($this->languageService->loadLanguages());

        $languageCreateEnglish = $this->languageService->newLanguageCreateStruct();
        $languageCreateEnglish->enabled = false;
        $languageCreateEnglish->name = 'English';
        $languageCreateEnglish->languageCode = 'eng-NZ';

        $language = $this->languageService->createLanguage($languageCreateEnglish);

        // Delete the newly created language
        $this->languageService->deleteLanguage($language);

        // +1 -1
        self::assertEquals($beforeCount, count($this->languageService->loadLanguages()));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Could not find \'Language\' with identifier \'eng-NZ\'');

        // ensure just created & deleted language doesn't exist
        $this->languageService->loadLanguage($languageCreateEnglish->languageCode);
    }

    /**
     * NOTE: This test has a dependency against several methods in the content
     * service, but because there is no topological sort for test dependencies
     * we cannot declare them here.
     *
     * @depends testDeleteLanguage
     */
    public function testDeleteLanguageThrowsInvalidArgumentException(): void
    {
        $editorsGroupId = $this->generateId('group', 13);
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $languageCreateEnglish = $this->languageService->newLanguageCreateStruct();
        $languageCreateEnglish->enabled = true;
        $languageCreateEnglish->name = 'English';
        $languageCreateEnglish->languageCode = 'eng-NZ';

        $language = $this->languageService->createLanguage($languageCreateEnglish);

        $contentService = $this->repository->getContentService();

        // Get metadata update struct and set new language as main language.
        $metadataUpdate = $contentService->newContentMetadataUpdateStruct();
        $metadataUpdate->mainLanguageCode = 'eng-NZ';

        // Update content object
        $contentService->updateContentMetadata(
            $contentService->loadContentInfo($editorsGroupId),
            $metadataUpdate
        );

        // This call will fail with an "InvalidArgumentException", because the
        // new language is used by a content object.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'language\' is invalid: Cannot delete language: some content still references the language');
        $this->languageService->deleteLanguage($language);
    }

    public function testGetDefaultLanguageCode(): void
    {
        self::assertMatchesRegularExpression(
            '(^[a-z]{3}-[A-Z]{2}$)',
            $this->languageService->getDefaultLanguageCode()
        );
    }

    /**
     * @depends testCreateLanguage
     */
    public function testCreateLanguageInTransactionWithRollback(): void
    {
        // Start a new transaction
        $this->repository->beginTransaction();

        try {
            // Get create struct and set properties
            $languageCreate = $this->languageService->newLanguageCreateStruct();
            $languageCreate->enabled = true;
            $languageCreate->name = 'English (New Zealand)';
            $languageCreate->languageCode = 'eng-NZ';

            // Create new language
            $this->languageService->createLanguage($languageCreate);
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $this->repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $this->repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $this->languageService->loadLanguage('eng-NZ');
        } catch (NotFoundException $e) {
            // Expected execution path
        }

        self::assertTrue(isset($e), 'Can still load language after rollback');
    }

    /**
     * @depends testCreateLanguage
     */
    public function testCreateLanguageInTransactionWithCommit(): void
    {
        $this->repository->beginTransaction();

        try {
            $languageCreate = $this->languageService->newLanguageCreateStruct();
            $languageCreate->enabled = true;
            $languageCreate->name = 'English (New Zealand)';
            $languageCreate->languageCode = 'eng-NZ';

            // Create new language
            $this->languageService->createLanguage($languageCreate);

            // Commit all changes
            $this->repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $this->repository->rollback();
            throw $e;
        }

        $language = $this->languageService->loadLanguage('eng-NZ');

        self::assertEquals('eng-NZ', $language->languageCode);
    }

    /**
     * @depends testUpdateLanguageName
     */
    public function testUpdateLanguageNameInTransactionWithRollback(): void
    {
        $this->repository->beginTransaction();

        try {
            $language = $this->languageService->loadLanguage('eng-US');

            $this->languageService->updateLanguageName($language, 'My English');
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $this->repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $this->repository->rollback();

        // Load updated version, name will still be "English (American)"
        $updatedLanguage = $this->languageService->loadLanguage('eng-US');

        self::assertEquals('English (American)', $updatedLanguage->name);
    }

    /**
     * @depends testUpdateLanguageName
     */
    public function testUpdateLanguageNameInTransactionWithCommit(): void
    {
        $this->repository->beginTransaction();

        try {
            $language = $this->languageService->loadLanguage('eng-US');

            $this->languageService->updateLanguageName($language, 'My English');

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        // Load updated version, name will be "My English"
        $updatedLanguage = $this->languageService->loadLanguage('eng-US');

        self::assertEquals('My English', $updatedLanguage->name);
    }
}
