<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as BaseLanguageHandler;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use LogicException;

/**
 * Language Handler.
 */
class Handler implements BaseLanguageHandler
{
    /**
     * Language Gateway.
     *
     * @var Gateway
     */
    protected $languageGateway;

    /**
     * Language Mapper.
     *
     * @var Mapper
     */
    protected $languageMapper;

    /**
     * Creates a new Language Handler.
     *
     * @param Gateway $languageGateway
     * @param Mapper $languageMapper
     */
    public function __construct(
        Gateway $languageGateway,
        Mapper $languageMapper
    ) {
        $this->languageGateway = $languageGateway;
        $this->languageMapper = $languageMapper;
    }

    /**
     * Create a new language.
     *
     * @param CreateStruct $struct
     *
     * @return Language
     */
    public function create(CreateStruct $struct)
    {
        $language = $this->languageMapper->createLanguageFromCreateStruct(
            $struct
        );
        $language->id = $this->languageGateway->insertLanguage($language);

        return $language;
    }

    /**
     * Update language.
     *
     * @param Language $language
     */
    public function update(Language $language)
    {
        $this->languageGateway->updateLanguage($language);
    }

    /**
     * Get language by id.
     *
     * @param mixed $id
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If language could not be found by $id
     *
     * @return Language
     */
    public function load($id): Language
    {
        $languages = $this->languageMapper->extractLanguagesFromRows(
            $this->languageGateway->loadLanguageListData([$id])
        );

        if (count($languages) < 1) {
            throw new NotFoundException('Language', $id);
        }

        return reset($languages);
    }

    /**
     * {@inheritdoc}
     */
    public function loadList(array $ids): iterable
    {
        return $this->languageMapper->extractLanguagesFromRows(
            $this->languageGateway->loadLanguageListData($ids),
            'id'
        );
    }

    /**
     * Get language by Language Code (eg: eng-GB).
     *
     * @param string $languageCode
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If language could not be found by $languageCode
     *
     * @return Language
     */
    public function loadByLanguageCode($languageCode): Language
    {
        $languages = $this->languageMapper->extractLanguagesFromRows(
            $this->languageGateway->loadLanguageListDataByLanguageCode([$languageCode])
        );

        if (count($languages) < 1) {
            throw new NotFoundException('Language', $languageCode);
        }

        return reset($languages);
    }

    /**
     * {@inheritdoc}
     */
    public function loadListByLanguageCodes(array $languageCodes): iterable
    {
        return $this->languageMapper->extractLanguagesFromRows(
            $this->languageGateway->loadLanguageListDataByLanguageCode($languageCodes)
        );
    }

    /**
     * Get all languages.
     *
     * @return Language[]
     */
    public function loadAll()
    {
        return $this->languageMapper->extractLanguagesFromRows(
            $this->languageGateway->loadAllLanguagesData()
        );
    }

    /**
     * Delete a language.
     *
     * @param mixed $id
     *
     * @throws LogicException If language could not be deleted
     */
    public function delete($id)
    {
        if (!$this->languageGateway->canDeleteLanguage($id)) {
            throw new LogicException('Cannot delete language: some content still references the language');
        }

        $this->languageGateway->deleteLanguage($id);
    }
}
