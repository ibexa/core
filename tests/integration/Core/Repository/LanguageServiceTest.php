<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LanguageCreateStruct;

/**
 * Test case for operations in the LanguageService using in memory storage.
 *
 * @covers \Ibexa\Contracts\Core\Repository\LanguageService
 *
 * @group integration
 * @group language
 */
class LanguageServiceTest extends BaseTestCase
{
    /**
     * Test for the newLanguageCreateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::newLanguageCreateStruct
     */
    public function testNewLanguageCreateStruct()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        /* END: Use Case */

        self::assertInstanceOf(
            LanguageCreateStruct::class,
            $languageCreate
        );

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
     * Test for the createLanguage() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Language
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage
     *
     * @depends testNewLanguageCreateStruct
     */
    public function testCreateLanguage()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English (New Zealand)';
        $languageCreate->languageCode = 'eng-NZ';

        $language = $languageService->createLanguage($languageCreate);
        /* END: Use Case */

        self::assertInstanceOf(
            Language::class,
            $language
        );

        return $language;
    }

    /**
     * Test for the createLanguage() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Language $language
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage
     *
     * @depends testCreateLanguage
     */
    public function testCreateLanguageSetsIdPropertyOnReturnedLanguage($language)
    {
        self::assertNotNull($language->id);
    }

    /**
     * Test for the createLanguage() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Language $language
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage
     *
     * @depends testCreateLanguage
     */
    public function testCreateLanguageSetsExpectedProperties($language)
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
     * Test for the createLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage
     *
     * @depends testCreateLanguage
     */
    public function testCreateLanguageThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'languageCreateStruct\' is invalid: language with the "nor-NO" language code already exists');

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'Norwegian';
        $languageCreate->languageCode = 'nor-NO';

        $languageService->createLanguage($languageCreate);

        // This call should fail with an InvalidArgumentException, because
        // the language code "nor-NO" already exists.
        $languageService->createLanguage($languageCreate);
        /* END: Use Case */
    }

    /**
     * Test for the loadLanguageById() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguageById
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguageListById
     *
     * @depends testCreateLanguage
     */
    public function testLoadLanguageById()
    {
        $repository = $this->getRepository();

        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = false;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $languageId = $languageService->createLanguage($languageCreate)->id;

        $language = $languageService->loadLanguageById($languageId);

        self::assertInstanceOf(
            Language::class,
            $language
        );

        $languages = iterator_to_array($languageService->loadLanguageListById([$languageId]));

        self::assertIsIterable($languages);
        self::assertCount(1, $languages);
        self::assertInstanceOf(Language::class, $languages[$languageId]);
    }

    /**
     * Test for the loadLanguageById() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguageById
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguageListById
     *
     * @depends testLoadLanguageById
     */
    public function testLoadLanguageByIdThrowsNotFoundException()
    {
        $repository = $this->getRepository();

        $nonExistentLanguageId = $this->generateId('language', 2342);
        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languages = $languageService->loadLanguageListById([$nonExistentLanguageId]);

        self::assertIsIterable($languages);
        self::assertCount(0, $languages);

        $this->expectException(NotFoundException::class);

        $languageService->loadLanguageById($nonExistentLanguageId);
        /* END: Use Case */
    }

    /**
     * Test for the updateLanguageName() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::updateLanguageName
     *
     * @depends testLoadLanguageById
     */
    public function testUpdateLanguageName()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = false;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $languageId = $languageService->createLanguage($languageCreate)->id;

        $language = $languageService->loadLanguageById($languageId);

        $updatedLanguage = $languageService->updateLanguageName(
            $language,
            'New language name.'
        );
        /* END: Use Case */

        // Verify that the service returns an updated language instance.
        self::assertInstanceOf(
            Language::class,
            $updatedLanguage
        );

        // Verify that the service also persists the changes
        $updatedLanguage = $languageService->loadLanguageById($languageId);
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

    /**
     * Test service method for updating language name throwing InvalidArgumentException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::updateLanguageName
     */
    public function testUpdateLanguageNameThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'newName\' is invalid: \'\' is incorrect value');

        $repository = $this->getRepository();
        $languageService = $repository->getContentLanguageService();

        $language = $languageService->loadLanguage('eng-GB');
        $languageService->updateLanguageName($language, '');
    }

    /**
     * Test for the enableLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::enableLanguage
     *
     * @depends testLoadLanguageById
     */
    public function testEnableLanguage()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = false;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $language = $languageService->createLanguage($languageCreate);

        // Now lets enable the newly created language
        $languageService->enableLanguage($language);

        $enabledLanguage = $languageService->loadLanguageById($language->id);
        /* END: Use Case */

        self::assertTrue($enabledLanguage->enabled);
    }

    /**
     * Test for the disableLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::disableLanguage
     *
     * @depends testLoadLanguageById
     */
    public function testDisableLanguage()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $language = $languageService->createLanguage($languageCreate);

        // Now lets disable the newly created language
        $languageService->disableLanguage($language);

        $enabledLanguage = $languageService->loadLanguageById($language->id);
        /* END: Use Case */

        self::assertFalse($enabledLanguage->enabled);
    }

    /**
     * Test for the loadLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguage
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguageListByCode
     *
     * @depends testCreateLanguage
     */
    public function testLoadLanguage()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreate = $languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;
        $languageCreate->name = 'English';
        $languageCreate->languageCode = 'eng-NZ';

        $languageId = $languageService->createLanguage($languageCreate)->id;

        // Now load the newly created language by it's language code
        $language = $languageService->loadLanguage('eng-NZ');
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'id' => $languageId,
                'languageCode' => 'eng-NZ',
                'name' => 'English',
                'enabled' => true,
            ],
            $language
        );

        $languages = iterator_to_array($languageService->loadLanguageListByCode(['eng-NZ']));

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
     * Test for the loadLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguage
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguageListByCode
     *
     * @depends testLoadLanguage
     */
    public function testLoadLanguageThrowsNotFoundException()
    {
        $repository = $this->getRepository();

        $languageService = $repository->getContentLanguageService();

        $languages = $languageService->loadLanguageListByCode(['fre-FR']);

        self::assertIsIterable($languages);
        self::assertCount(0, $languages);

        $this->expectException(NotFoundException::class);

        $languageService->loadLanguage('fre-FR');
    }

    /**
     * Test service method for loading language throwing InvalidArgumentException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguage
     */
    public function testLoadLanguageThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'languageCode\' is invalid: language code has an invalid value');

        $repository = $this->getRepository();

        $repository->getContentLanguageService()->loadLanguage('');
    }

    /**
     * Test for the loadLanguages() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguages
     *
     * @depends testCreateLanguage
     * @depends testLoadLanguage
     */
    public function testLoadLanguages()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        // Create some languages
        $languageCreateEnglish = $languageService->newLanguageCreateStruct();
        $languageCreateEnglish->enabled = false;
        $languageCreateEnglish->name = 'English';
        $languageCreateEnglish->languageCode = 'eng-NZ';

        $languageCreateFrench = $languageService->newLanguageCreateStruct();
        $languageCreateFrench->enabled = false;
        $languageCreateFrench->name = 'French';
        $languageCreateFrench->languageCode = 'fre-FR';

        $languageService->createLanguage($languageCreateEnglish);
        $languageService->createLanguage($languageCreateFrench);

        $languages = $languageService->loadLanguages();
        self::assertIsArray($languages);
        foreach ($languages as $language) {
            self::assertInstanceOf(Language::class, $language);
            $singleLanguage = $languageService->loadLanguage($language->languageCode);
            $this->assertStructPropertiesCorrect(
                $singleLanguage,
                $language,
                ['id', 'languageCode', 'name', 'enabled']
            );
        }
        /* END: Use Case */

        // eng-US, eng-GB, ger-DE + 2 newly created
        self::assertCount(5, $languages);
    }

    /**
     * Test for the loadLanguages() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::loadLanguages
     *
     * @depends testCreateLanguage
     */
    public function loadLanguagesReturnsAnEmptyArrayByDefault()
    {
        $repository = $this->getRepository();

        $languageService = $repository->getContentLanguageService();

        self::assertSame([], $languageService->loadLanguages());
    }

    /**
     * Test for the deleteLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::deleteLanguage
     *
     * @depends testLoadLanguages
     */
    public function testDeleteLanguage()
    {
        $repository = $this->getRepository();
        $languageService = $repository->getContentLanguageService();

        $beforeCount = count($languageService->loadLanguages());

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        $languageCreateEnglish = $languageService->newLanguageCreateStruct();
        $languageCreateEnglish->enabled = false;
        $languageCreateEnglish->name = 'English';
        $languageCreateEnglish->languageCode = 'eng-NZ';

        $language = $languageService->createLanguage($languageCreateEnglish);

        // Delete the newly created language
        $languageService->deleteLanguage($language);
        /* END: Use Case */

        // +1 -1
        self::assertEquals($beforeCount, count($languageService->loadLanguages()));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Could not find \'Language\' with identifier \'eng-NZ\'');

        // ensure just created & deleted language doesn't exist
        $languageService->loadLanguage($languageCreateEnglish->languageCode);
        self::fail('Language is still returned after being deleted');
    }

    /**
     * Test for the deleteLanguage() method.
     *
     * NOTE: This test has a dependency against several methods in the content
     * service, but because there is no topological sort for test dependencies
     * we cannot declare them here.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::deleteLanguage
     *
     * @depends testDeleteLanguage
     */
    public function testDeleteLanguageThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'language\' is invalid: Cannot delete language: some content still references the language');

        $repository = $this->getRepository();

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $languageService = $repository->getContentLanguageService();

        $languageCreateEnglish = $languageService->newLanguageCreateStruct();
        $languageCreateEnglish->enabled = true;
        $languageCreateEnglish->name = 'English';
        $languageCreateEnglish->languageCode = 'eng-NZ';

        $language = $languageService->createLanguage($languageCreateEnglish);

        $contentService = $repository->getContentService();

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
        $languageService->deleteLanguage($language);
        /* END: Use Case */
    }

    /**
     * Test for the getDefaultLanguageCode() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::getDefaultLanguageCode
     */
    public function testGetDefaultLanguageCode()
    {
        $repository = $this->getRepository();
        $languageService = $repository->getContentLanguageService();

        self::assertRegExp(
            '(^[a-z]{3}\-[A-Z]{2}$)',
            $languageService->getDefaultLanguageCode()
        );
    }

    /**
     * Test for the createLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage
     *
     * @depends testCreateLanguage
     */
    public function testCreateLanguageInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Get create struct and set properties
            $languageCreate = $languageService->newLanguageCreateStruct();
            $languageCreate->enabled = true;
            $languageCreate->name = 'English (New Zealand)';
            $languageCreate->languageCode = 'eng-NZ';

            // Create new language
            $languageService->createLanguage($languageCreate);
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $languageService->loadLanguage('eng-NZ');
        } catch (NotFoundException $e) {
            // Expected execution path
        }
        /* END: Use Case */

        self::assertTrue(isset($e), 'Can still load language after rollback');
    }

    /**
     * Test for the createLanguage() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage
     *
     * @depends testCreateLanguage
     */
    public function testCreateLanguageInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Get create struct and set properties
            $languageCreate = $languageService->newLanguageCreateStruct();
            $languageCreate->enabled = true;
            $languageCreate->name = 'English (New Zealand)';
            $languageCreate->languageCode = 'eng-NZ';

            // Create new language
            $languageService->createLanguage($languageCreate);

            // Commit all changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Load new language
        $language = $languageService->loadLanguage('eng-NZ');
        /* END: Use Case */

        self::assertEquals('eng-NZ', $language->languageCode);
    }

    /**
     * Test for the updateLanguageName() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::updateLanguageName
     *
     * @depends testUpdateLanguageName
     */
    public function testUpdateLanguageNameInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Load an existing language
            $language = $languageService->loadLanguage('eng-US');

            // Update the language name
            $languageService->updateLanguageName($language, 'My English');
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $repository->rollback();

        // Load updated version, name will still be "English (American)"
        $updatedLanguage = $languageService->loadLanguage('eng-US');
        /* END: Use Case */

        self::assertEquals('English (American)', $updatedLanguage->name);
    }

    /**
     * Test for the updateLanguageName() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::updateLanguageName
     *
     * @depends testUpdateLanguageName
     */
    public function testUpdateLanguageNameInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $languageService = $repository->getContentLanguageService();

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Load an existing language
            $language = $languageService->loadLanguage('eng-US');

            // Update the language name
            $languageService->updateLanguageName($language, 'My English');

            // Commit all changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Load updated version, name will be "My English"
        $updatedLanguage = $languageService->loadLanguage('eng-US');
        /* END: Use Case */

        self::assertEquals('My English', $updatedLanguage->name);
    }
}
