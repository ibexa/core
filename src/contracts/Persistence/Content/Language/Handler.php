<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;

/**
 * Language Handler interface.
 */
interface Handler
{
    /**
     * Create a new language.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Language\CreateStruct $struct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    public function create(CreateStruct $struct);

    /**
     * Update language.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Language $struct
     */
    public function update(Language $struct);

    /**
     * Get language by id.
     *
     * @param mixed $id
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If language could not be found by $id
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    public function load($id);

    /**
     * Get list of languages by id.
     *
     * Missing items (NotFound) will be missing from the returned iterable and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * @param array $ids
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language[]|iterable
     */
    public function loadList(array $ids): iterable;

    /**
     * Get language by Language Code (eg: eng-GB).
     *
     * @param string $languageCode
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If language could not be found by $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language
     */
    public function loadByLanguageCode($languageCode);

    /**
     * Get list of languages by Language Code (eg: eng-GB).
     *
     * Missing items (NotFound) will be missing from the returned iterable and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * @param string[] $languageCodes
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language[]|iterable
     */
    public function loadListByLanguageCodes(array $languageCodes): iterable;

    /**
     * Get all languages.
     *
     * Return list of languages where key of hash is language code.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language[]
     */
    public function loadAll();

    /**
     * Delete a language.
     *
     * @throws \LogicException If language could not be deleted
     *
     * @param mixed $id
     */
    public function delete($id);
}
