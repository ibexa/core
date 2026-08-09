<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway as ObjectStateGateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway as ContentTypeGateway;

/**
 * Content Model language gateway.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const CONTENT_LANGUAGE_TABLE = 'ibexa_content_language';

    /**
     * A map of language-related table name to the single column identifying a real language id
     * that row references (an explicit id column, never a bitmask).
     *
     * "ibexa_content"/"ibexa_content_version" (their "initial_language_id"), the URL alias
     * translations table, and the Legacy Search Engine's word index are checked explicitly in
     * {@see DoctrineDatabase::canDeleteLanguage()} instead of via this map.
     *
     * It depends on the schema defined in
     * <code>./src/bundle/Core/Resources/config/storage/legacy/schema.yaml</code>
     */
    public const MULTILINGUAL_TABLES_COLUMNS = [
        ObjectStateGateway::OBJECT_STATE_TABLE => ['default_language_id'],
        ObjectStateGateway::OBJECT_STATE_GROUP_LANGUAGE_TABLE => ['language_id'],
        ObjectStateGateway::OBJECT_STATE_GROUP_TABLE => ['default_language_id'],
        ObjectStateGateway::OBJECT_STATE_LANGUAGE_TABLE => ['language_id'],
        ContentTypeGateway::MULTILINGUAL_FIELD_DEFINITION_TABLE => ['language_id'],
        ContentTypeGateway::CONTENT_TYPE_NAME_TABLE => ['language_id'],
        ContentTypeGateway::CONTENT_TYPE_TABLE => ['initial_language_id'],
        ContentGateway::CONTENT_FIELD_TABLE => ['language_id'],
        ContentGateway::CONTENT_NAME_TABLE => ['language_id'],
    ];

    /**
     * Insert the given $language.
     */
    abstract public function insertLanguage(Language $language): int;

    /**
     * Update the data of the given $language.
     */
    abstract public function updateLanguage(Language $language): void;

    /**
     * Load data list for the Language with $ids.
     *
     * @param int[] $ids
     *
     * @return string[][]|iterable
     */
    abstract public function loadLanguageListData(array $ids): iterable;

    /**
     * Load data list for Languages by $languageCodes (eg: eng-GB).
     *
     * @param string[] $languageCodes
     *
     * @return string[][]|iterable
     */
    abstract public function loadLanguageListDataByLanguageCode(array $languageCodes): iterable;

    /**
     * Load the data for all languages.
     */
    abstract public function loadAllLanguagesData(): array;

    /**
     * Delete the language with $id.
     */
    abstract public function deleteLanguage(int $id): void;

    /**
     * Check whether a language may be deleted.
     */
    abstract public function canDeleteLanguage(int $id): bool;

    /**
     * Loads which languages each of the given Content ids is translated into, from
     * "ibexa_content_translation" (the relational replacement for "ibexa_content.language_mask").
     *
     * @param int[] $contentIds
     *
     * @return array<int, int[]> Content id => language ids
     */
    abstract public function loadContentTranslations(array $contentIds): array;

    /**
     * Loads which languages each of the given Content Version ids is translated into, from
     * "ibexa_content_version_translation" (the relational replacement for
     * "ibexa_content_version.language_mask").
     *
     * @param int[] $versionIds
     *
     * @return array<int, int[]> Version id => language ids
     */
    abstract public function loadVersionTranslations(array $versionIds): array;
}
