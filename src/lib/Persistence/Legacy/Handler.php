<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy;

use Ibexa\Contracts\Core\Persistence\Bookmark\Handler as BookmarkHandler;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler as TrashHandler;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler as ObjectStateHandler;
use Ibexa\Contracts\Core\Persistence\Content\Section\Handler as SectionHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\UrlAlias\Handler as UrlAliasHandler;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard\Handler as UrlWildcardHandler;
use Ibexa\Contracts\Core\Persistence\Handler as HandlerInterface;
use Ibexa\Contracts\Core\Persistence\Notification\Handler as NotificationHandler;
use Ibexa\Contracts\Core\Persistence\Setting\Handler as SettingHandler;
use Ibexa\Contracts\Core\Persistence\TransactionHandler;
use Ibexa\Contracts\Core\Persistence\TransactionHandler as SPITransactionHandler;
use Ibexa\Contracts\Core\Persistence\User\Handler as UserHandler;
use Ibexa\Contracts\Core\Persistence\UserPreference\Handler as UserPreferenceHandler;
use Ibexa\Core\Persistence\Legacy\URL\Handler as UrlHandler;

/**
 * The main handler for Legacy Storage Engine.
 */
class Handler implements HandlerInterface
{
    /** @var ContentHandler */
    protected $contentHandler;

    /** @var ContentTypeHandler */
    protected $contentTypeHandler;

    /** @var LanguageHandler */
    protected $languageHandler;

    /** @var LocationHandler */
    protected $locationHandler;

    /** @var ObjectStateHandler */
    protected $objectStateHandler;

    /** @var SectionHandler */
    protected $sectionHandler;

    /** @var TransactionHandler */
    protected $transactionHandler;

    /** @var TrashHandler */
    protected $trashHandler;

    /** @var UrlAliasHandler */
    protected $urlAliasHandler;

    /** @var UrlWildcardHandler */
    protected $urlWildcardHandler;

    /** @var UserHandler */
    protected $userHandler;

    /** @var UrlHandler */
    protected $urlHandler;

    /** @var BookmarkHandler */
    protected $bookmarkHandler;

    /** @var NotificationHandler */
    protected $notificationHandler;

    /** @var UserPreferenceHandler */
    protected $userPreferenceHandler;

    /** @var SettingHandler */
    private $settingHandler;

    public function __construct(
        ContentHandler $contentHandler,
        ContentTypeHandler $contentTypeHandler,
        LanguageHandler $languageHandler,
        LocationHandler $locationHandler,
        ObjectStateHandler $objectStateHandler,
        SectionHandler $sectionHandler,
        SPITransactionHandler $transactionHandler,
        TrashHandler $trashHandler,
        UrlAliasHandler $urlAliasHandler,
        UrlWildcardHandler $urlWildcardHandler,
        UserHandler $userHandler,
        UrlHandler $urlHandler,
        BookmarkHandler $bookmarkHandler,
        NotificationHandler $notificationHandler,
        UserPreferenceHandler $userPreferenceHandler,
        SettingHandler $settingHandler
    ) {
        $this->contentHandler = $contentHandler;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->languageHandler = $languageHandler;
        $this->locationHandler = $locationHandler;
        $this->objectStateHandler = $objectStateHandler;
        $this->sectionHandler = $sectionHandler;
        $this->transactionHandler = $transactionHandler;
        $this->trashHandler = $trashHandler;
        $this->urlAliasHandler = $urlAliasHandler;
        $this->urlWildcardHandler = $urlWildcardHandler;
        $this->userHandler = $userHandler;
        $this->urlHandler = $urlHandler;
        $this->bookmarkHandler = $bookmarkHandler;
        $this->notificationHandler = $notificationHandler;
        $this->userPreferenceHandler = $userPreferenceHandler;
        $this->settingHandler = $settingHandler;
    }

    public function contentHandler()
    {
        return $this->contentHandler;
    }

    public function contentTypeHandler()
    {
        return $this->contentTypeHandler;
    }

    public function contentLanguageHandler()
    {
        return $this->languageHandler;
    }

    public function locationHandler()
    {
        return $this->locationHandler;
    }

    public function objectStateHandler()
    {
        return $this->objectStateHandler;
    }

    public function sectionHandler()
    {
        return $this->sectionHandler;
    }

    public function trashHandler()
    {
        return $this->trashHandler;
    }

    public function urlAliasHandler()
    {
        return $this->urlAliasHandler;
    }

    public function urlWildcardHandler()
    {
        return $this->urlWildcardHandler;
    }

    public function userHandler()
    {
        return $this->userHandler;
    }

    public function urlHandler()
    {
        return $this->urlHandler;
    }

    public function bookmarkHandler()
    {
        return $this->bookmarkHandler;
    }

    public function settingHandler(): SettingHandler
    {
        return $this->settingHandler;
    }

    /**
     * @return NotificationHandler
     */
    public function notificationHandler(): NotificationHandler
    {
        return $this->notificationHandler;
    }

    /**
     * @return UserPreferenceHandler
     */
    public function userPreferenceHandler(): UserPreferenceHandler
    {
        return $this->userPreferenceHandler;
    }

    /**
     * @return TransactionHandler
     */
    public function transactionHandler()
    {
        return $this->transactionHandler;
    }
}
