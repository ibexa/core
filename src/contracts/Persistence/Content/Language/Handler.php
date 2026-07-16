<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

/**
 * Language Handler interface.
 */
interface Handler
{
    /**
     * Create a new language.
     *
     * @param CreateStruct $struct
     *
     * @return Language
     */
    public function create(CreateStruct $struct);

    /**
     * Update language.
     *
     * @param Language $struct
     */
    public function update(Language $struct);

    /**
     * Get language by id.
     *
     * @param mixed $id
     *
     * @throws NotFoundException If language could not be found by $id
     *
     * @return Language
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
     * @return Language[]|iterable
     */
    public function loadList(array $ids): iterable;

    /**
     * Get language by Language Code (eg: eng-GB).
     *
     * @param string $languageCode
     *
     * @throws NotFoundException If language could not be found by $languageCode
     *
     * @return Language
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
     * @return Language[]|iterable
     */
    public function loadListByLanguageCodes(array $languageCodes): iterable;

    /**
     * Get all languages.
     *
     * Return list of languages where key of hash is language code.
     *
     * @return Language[]
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
