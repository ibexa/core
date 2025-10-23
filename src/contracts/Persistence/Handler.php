<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence;

use Ibexa\Contracts\Core\Persistence\Setting\Handler as SettingHandler;

/**
 * The main handler for Storage Engine.
 */
interface Handler
{
    /**
     * @return Content\Handler
     */
    public function contentHandler();

    /**
     * @return Content\Type\Handler
     */
    public function contentTypeHandler();

    /**
     * @return Content\Language\Handler
     */
    public function contentLanguageHandler();

    /**
     * @return Content\Location\Handler
     */
    public function locationHandler();

    /**
     * @return Content\ObjectState\Handler
     */
    public function objectStateHandler();

    /**
     * @return Content\Location\Trash\Handler
     */
    public function trashHandler();

    /**
     * @return User\Handler
     */
    public function userHandler();

    /**
     * @return Content\Section\Handler
     */
    public function sectionHandler();

    /**
     * @return Content\UrlAlias\Handler
     */
    public function urlAliasHandler();

    /**
     * @return Content\UrlWildcard\Handler
     */
    public function urlWildcardHandler();

    /**
     * @return \Ibexa\Core\Persistence\Legacy\URL\Handler
     */
    public function urlHandler();

    /**
     * @return Bookmark\Handler
     */
    public function bookmarkHandler();

    /**
     * @return Notification\Handler
     */
    public function notificationHandler();

    /**
     * @return UserPreference\Handler
     */
    public function userPreferenceHandler();

    /**
     * @return TransactionHandler
     */
    public function transactionHandler();

    public function settingHandler(): SettingHandler;
}
