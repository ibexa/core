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
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway as UrlAliasGateway;

/**
 * Content Model language gateway.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const CONTENT_LANGUAGE_TABLE = 'ibexa_content_language';

    /**
     * A map of language-related table name to its language column.
     *
     * The first column is considered to be a language bitmask.
     * The second, optional, column is an explicit language id.
     *
     * It depends on the schema defined in
     * <code>./src/bundle/Core/Resources/config/storage/legacy/schema.yaml</code>
     */
    public const MULTILINGUAL_TABLES_COLUMNS = [
        ObjectStateGateway::OBJECT_STATE_TABLE => ['language_mask', 'default_language_id'],
        ObjectStateGateway::OBJECT_STATE_GROUP_LANGUAGE_TABLE => ['language_id'],
        ObjectStateGateway::OBJECT_STATE_GROUP_TABLE => ['language_mask', 'default_language_id'],
        ObjectStateGateway::OBJECT_STATE_LANGUAGE_TABLE => ['language_id'],
        ContentTypeGateway::MULTILINGUAL_FIELD_DEFINITION_TABLE => ['language_id'],
        ContentTypeGateway::CONTENT_TYPE_NAME_TABLE => ['language_id'],
        ContentTypeGateway::CONTENT_TYPE_TABLE => ['language_mask', 'initial_language_id'],
        ContentGateway::CONTENT_FIELD_TABLE => ['language_id'],
        ContentGateway::CONTENT_NAME_TABLE => ['language_id'],
        ContentGateway::CONTENT_VERSION_TABLE => ['language_mask', 'initial_language_id'],
        ContentGateway::CONTENT_ITEM_TABLE => ['language_mask', 'initial_language_id'],
        UrlAliasGateway::TABLE => ['lang_mask'],
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
}
