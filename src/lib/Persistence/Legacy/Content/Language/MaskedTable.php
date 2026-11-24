<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Language;

use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway as UrlAliasGateway;

/**
 * A table carrying a legacy language bitmask, together with the translation table it is backfilled into.
 *
 * @internal
 */
enum MaskedTable: string
{
    case Content = 'content';
    case ContentVersion = 'content_version';
    case UrlAlias = 'url_alias';

    /**
     * @return non-empty-string
     */
    public function sourceTable(): string
    {
        return match ($this) {
            self::Content => ContentGateway::CONTENT_ITEM_TABLE,
            self::ContentVersion => ContentGateway::CONTENT_VERSION_TABLE,
            self::UrlAlias => UrlAliasGateway::TABLE,
        };
    }

    /**
     * @return non-empty-string
     */
    public function maskColumn(): string
    {
        return match ($this) {
            self::Content, self::ContentVersion => 'language_mask',
            self::UrlAlias => 'lang_mask',
        };
    }

    /**
     * @return non-empty-string
     */
    public function translationTable(): string
    {
        return match ($this) {
            self::Content => ContentGateway::CONTENT_TRANSLATION_TABLE,
            self::ContentVersion => ContentGateway::CONTENT_VERSION_TRANSLATION_TABLE,
            self::UrlAlias => UrlAliasGateway::TRANSLATION_TABLE,
        };
    }

    /**
     * Maps the translation table key columns to the source table columns.
     *
     * @return non-empty-array<string, string>
     */
    public function keyColumns(): array
    {
        return match ($this) {
            self::Content => ['content_id' => 'id'],
            self::ContentVersion => ['content_version_id' => 'id'],
            self::UrlAlias => ['parent' => 'parent', 'text_md5' => 'text_md5'],
        };
    }

    /**
     * @return non-empty-string
     */
    public function alwaysAvailableColumn(): string
    {
        return 'always_available';
    }
}
