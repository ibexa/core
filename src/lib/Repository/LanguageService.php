<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Persistence\Content\Language as SPILanguage;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\LanguageService as LanguageServiceInterface;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LanguageCreateStruct;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use LogicException;

/**
 * Language service, used for language operations.
 */
class LanguageService implements LanguageServiceInterface
{
    /** @var Repository */
    protected $repository;

    /** @var Handler */
    protected $languageHandler;

    /** @var array */
    protected $settings;

    /** @var PermissionResolver */
    private $permissionResolver;

    /**
     * Setups service with reference to repository object that created it & corresponding handler.
     *
     * @param Repository $repository
     * @param Handler $languageHandler
     * @param array $settings
     */
    public function __construct(
        RepositoryInterface $repository,
        Handler $languageHandler,
        PermissionResolver $permissionResolver,
        array $settings = []
    ) {
        $this->repository = $repository;
        $this->languageHandler = $languageHandler;
        $this->permissionResolver = $permissionResolver;
        // Union makes sure default settings are ignored if provided in argument
        $this->settings = $settings + [
            'languages' => ['eng-GB'],
        ];
    }

    /**
     * Creates the a new Language in the content repository.
     *
     * @param LanguageCreateStruct $languageCreateStruct
     *
     * @return Language
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If user does not have access to content translations
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the languageCode already exists
     */
    public function createLanguage(LanguageCreateStruct $languageCreateStruct): Language
    {
        if (!is_string($languageCreateStruct->name) || empty($languageCreateStruct->name)) {
            throw new InvalidArgumentValue('name', $languageCreateStruct->name, 'LanguageCreateStruct');
        }

        if (!is_string($languageCreateStruct->languageCode) || empty($languageCreateStruct->languageCode)) {
            throw new InvalidArgumentValue('languageCode', $languageCreateStruct->languageCode, 'LanguageCreateStruct');
        }

        if (!$this->permissionResolver->canUser('content', 'translations', $languageCreateStruct)) {
            throw new UnauthorizedException('content', 'translations');
        }

        try {
            if ($this->loadLanguage($languageCreateStruct->languageCode) !== null) {
                throw new InvalidArgumentException(
                    'languageCreateStruct',
                    sprintf('language with the "%s" language code already exists', $languageCreateStruct->languageCode)
                );
            }
        } catch (APINotFoundException $e) {
            // Do nothing
        }

        $createStruct = new CreateStruct(
            [
                'languageCode' => $languageCreateStruct->languageCode,
                'name' => $languageCreateStruct->name,
                'isEnabled' => $languageCreateStruct->enabled,
            ]
        );

        $this->repository->beginTransaction();
        try {
            $createdLanguage = $this->languageHandler->create($createStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->buildDomainObject($createdLanguage);
    }

    /**
     * Changes the name of the language in the content repository.
     *
     * @param Language $language
     * @param string $newName
     *
     * @return Language
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if languageCode argument
     *         is not string
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If user does not have access to content translations
     */
    public function updateLanguageName(
        Language $language,
        string $newName
    ): Language {
        if (empty($newName)) {
            throw new InvalidArgumentValue('newName', $newName);
        }

        if (!$this->permissionResolver->canUser('content', 'translations', $language)) {
            throw new UnauthorizedException('content', 'translations');
        }

        $loadedLanguage = $this->loadLanguageById($language->id);

        $updateLanguageStruct = new SPILanguage(
            [
                'id' => $loadedLanguage->id,
                'languageCode' => $loadedLanguage->languageCode,
                'name' => $newName,
                'isEnabled' => $loadedLanguage->enabled,
            ]
        );

        $this->repository->beginTransaction();
        try {
            $this->languageHandler->update($updateLanguageStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadLanguageById($loadedLanguage->id);
    }

    /**
     * Enables a language.
     *
     * @param Language $language
     *
     * @return Language
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If user does not have access to content translations
     */
    public function enableLanguage(Language $language): Language
    {
        if (!$this->permissionResolver->canUser('content', 'translations', $language)) {
            throw new UnauthorizedException('content', 'translations');
        }

        $loadedLanguage = $this->loadLanguageById($language->id);

        $updateLanguageStruct = new SPILanguage(
            [
                'id' => $loadedLanguage->id,
                'languageCode' => $loadedLanguage->languageCode,
                'name' => $loadedLanguage->name,
                'isEnabled' => true,
            ]
        );

        $this->repository->beginTransaction();
        try {
            $this->languageHandler->update($updateLanguageStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadLanguageById($loadedLanguage->id);
    }

    /**
     * Disables a language.
     *
     * @param Language $language
     *
     * @return Language
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If user does not have access to content translations
     */
    public function disableLanguage(Language $language): Language
    {
        if (!$this->permissionResolver->canUser('content', 'translations', $language)) {
            throw new UnauthorizedException('content', 'translations');
        }

        $loadedLanguage = $this->loadLanguageById($language->id);

        $updateLanguageStruct = new SPILanguage(
            [
                'id' => $loadedLanguage->id,
                'languageCode' => $loadedLanguage->languageCode,
                'name' => $loadedLanguage->name,
                'isEnabled' => false,
            ]
        );

        $this->repository->beginTransaction();
        try {
            $this->languageHandler->update($updateLanguageStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadLanguageById($loadedLanguage->id);
    }

    /**
     * Loads a Language from its language code ($languageCode).
     *
     * @param string $languageCode
     *
     * @return Language
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if languageCode argument
     *         is not string
     * @throws NotFoundException if language could not be found
     */
    public function loadLanguage(string $languageCode): Language
    {
        if (empty($languageCode)) {
            throw new InvalidArgumentException('languageCode', 'language code has an invalid value');
        }

        $language = $this->languageHandler->loadByLanguageCode($languageCode);

        return $this->buildDomainObject($language);
    }

    /**
     * Loads all Languages.
     *
     * @return Language[]
     */
    public function loadLanguages(): iterable
    {
        $languages = $this->languageHandler->loadAll();

        $returnArray = [];
        foreach ($languages as $language) {
            $returnArray[] = $this->buildDomainObject($language);
        }

        return $returnArray;
    }

    /**
     * Loads a Language by its id ($languageId).
     *
     * @param mixed $languageId
     *
     * @return Language
     *
     * @throws NotFoundException if language could not be found
     */
    public function loadLanguageById(int $languageId): Language
    {
        $language = $this->languageHandler->load($languageId);

        return $this->buildDomainObject($language);
    }

    /**
     * {@inheritdoc}
     */
    public function loadLanguageListByCode(array $languageCodes): iterable
    {
        $languages = $this->languageHandler->loadListByLanguageCodes($languageCodes);

        $returnArray = [];
        foreach ($languages as $language) {
            $returnArray[$language->languageCode] = $this->buildDomainObject($language);
        }

        return $returnArray;
    }

    /**
     * {@inheritdoc}
     */
    public function loadLanguageListById(array $languageIds): iterable
    {
        $languages = $this->languageHandler->loadList($languageIds);

        $returnArray = [];
        foreach ($languages as $language) {
            $returnArray[$language->id] = $this->buildDomainObject($language);
        }

        return $returnArray;
    }

    /**
     * Deletes  a language from content repository.
     *
     * @param Language $language
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if language can not be deleted
     *         because it is still assigned to some content / type / (...).
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If user does not have access to content translations
     */
    public function deleteLanguage(Language $language): void
    {
        if (!$this->permissionResolver->canUser('content', 'translations', $language)) {
            throw new UnauthorizedException('content', 'translations');
        }

        $loadedLanguage = $this->loadLanguageById($language->id);

        $this->repository->beginTransaction();
        try {
            $this->languageHandler->delete($loadedLanguage->id);
            $this->repository->commit();
        } catch (LogicException $e) {
            $this->repository->rollback();
            throw new InvalidArgumentException('language', $e->getMessage(), $e);
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Returns a configured default language code.
     *
     * @return string
     */
    public function getDefaultLanguageCode(): string
    {
        return $this->settings['languages'][0];
    }

    /**
     * Returns a configured list of prioritized languageCodes.
     *
     *
     * @return string[]
     */
    public function getPrioritizedLanguageCodeList()
    {
        return $this->settings['languages'];
    }

    /**
     * Instantiates an object to be used for creating languages.
     *
     * @return LanguageCreateStruct
     */
    public function newLanguageCreateStruct(): LanguageCreateStruct
    {
        return new LanguageCreateStruct();
    }

    /**
     * Builds Language domain object from ValueObject returned by Persistence API.
     *
     * @param SPILanguage $spiLanguage
     *
     * @return Language
     */
    protected function buildDomainObject(SPILanguage $spiLanguage)
    {
        return new Language(
            [
                'id' => $spiLanguage->id,
                'languageCode' => $spiLanguage->languageCode,
                'name' => $spiLanguage->name,
                'enabled' => $spiLanguage->isEnabled,
            ]
        );
    }
}
