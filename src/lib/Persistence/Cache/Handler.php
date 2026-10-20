<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandlerInterface;
use Ibexa\Contracts\Core\Persistence\Setting\Handler as SPISettingHandler;
use Ibexa\Contracts\Core\Persistence\TransactionHandler;
use Ibexa\Core\Persistence\Cache\BookmarkHandler as CacheBookmarkHandler;
use Ibexa\Core\Persistence\Cache\ContentHandler as CacheContentHandler;
use Ibexa\Core\Persistence\Cache\ContentLanguageHandler as CacheContentLanguageHandler;
use Ibexa\Core\Persistence\Cache\ContentTypeHandler as CacheContentTypeHandler;
use Ibexa\Core\Persistence\Cache\LocationHandler as CacheLocationHandler;
use Ibexa\Core\Persistence\Cache\NotificationHandler as CacheNotificationHandler;
use Ibexa\Core\Persistence\Cache\ObjectStateHandler as CacheObjectStateHandler;
use Ibexa\Core\Persistence\Cache\SectionHandler as CacheSectionHandler;
use Ibexa\Core\Persistence\Cache\SettingHandler as SettingHandler;
use Ibexa\Core\Persistence\Cache\TransactionHandler as CacheTransactionHandler;
use Ibexa\Core\Persistence\Cache\TrashHandler as CacheTrashHandler;
use Ibexa\Core\Persistence\Cache\UrlAliasHandler as CacheUrlAliasHandler;
use Ibexa\Core\Persistence\Cache\URLHandler as CacheUrlHandler;
use Ibexa\Core\Persistence\Cache\UrlWildcardHandler as CacheUrlWildcardHandler;
use Ibexa\Core\Persistence\Cache\UserHandler as CacheUserHandler;
use Ibexa\Core\Persistence\Cache\UserPreferenceHandler as CacheUserPreferenceHandler;

/**
 * Persistence Cache Handler class.
 */
class Handler implements PersistenceHandlerInterface
{
    /** @var PersistenceHandlerInterface */
    protected $persistenceHandler;

    /** @var CacheSectionHandler */
    protected $sectionHandler;

    /** @var CacheContentHandler */
    protected $contentHandler;

    /** @var CacheContentLanguageHandler */
    protected $contentLanguageHandler;

    /** @var CacheContentTypeHandler */
    protected $contentTypeHandler;

    /** @var CacheLocationHandler */
    protected $locationHandler;

    /** @var CacheUserHandler */
    protected $userHandler;

    /** @var CacheTrashHandler */
    protected $trashHandler;

    /** @var CacheUrlAliasHandler */
    protected $urlAliasHandler;

    /** @var CacheObjectStateHandler */
    protected $objectStateHandler;

    /** @var CacheTransactionHandler */
    protected $transactionHandler;

    /** @var CacheUrlHandler */
    protected $urlHandler;

    /** @var CacheBookmarkHandler */
    protected $bookmarkHandler;

    /** @var CacheNotificationHandler */
    protected $notificationHandler;

    /** @var CacheUserPreferenceHandler */
    protected $userPreferenceHandler;

    /** @var CacheUrlWildcardHandler */
    private $urlWildcardHandler;

    /** @var PersistenceLogger */
    protected $logger;

    /** @var SettingHandler */
    private $settingHandler;

    public function __construct(
        PersistenceHandlerInterface $persistenceHandler,
        CacheSectionHandler $sectionHandler,
        CacheLocationHandler $locationHandler,
        CacheContentHandler $contentHandler,
        CacheContentLanguageHandler $contentLanguageHandler,
        CacheContentTypeHandler $contentTypeHandler,
        CacheUserHandler $userHandler,
        CacheTransactionHandler $transactionHandler,
        CacheTrashHandler $trashHandler,
        CacheUrlAliasHandler $urlAliasHandler,
        CacheObjectStateHandler $objectStateHandler,
        CacheUrlHandler $urlHandler,
        CacheBookmarkHandler $bookmarkHandler,
        CacheNotificationHandler $notificationHandler,
        CacheUserPreferenceHandler $userPreferenceHandler,
        CacheUrlWildcardHandler $urlWildcardHandler,
        SettingHandler $settingHandler,
        PersistenceLogger $logger
    ) {
        $this->persistenceHandler = $persistenceHandler;
        $this->sectionHandler = $sectionHandler;
        $this->locationHandler = $locationHandler;
        $this->contentHandler = $contentHandler;
        $this->contentLanguageHandler = $contentLanguageHandler;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->userHandler = $userHandler;
        $this->transactionHandler = $transactionHandler;
        $this->trashHandler = $trashHandler;
        $this->urlAliasHandler = $urlAliasHandler;
        $this->objectStateHandler = $objectStateHandler;
        $this->urlHandler = $urlHandler;
        $this->bookmarkHandler = $bookmarkHandler;
        $this->notificationHandler = $notificationHandler;
        $this->userPreferenceHandler = $userPreferenceHandler;
        $this->urlWildcardHandler = $urlWildcardHandler;
        $this->settingHandler = $settingHandler;
        $this->logger = $logger;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Handler
     */
    public function contentHandler()
    {
        return $this->contentHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Handler
     */
    public function contentTypeHandler()
    {
        return $this->contentTypeHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language\Handler
     */
    public function contentLanguageHandler()
    {
        return $this->contentLanguageHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location\Handler
     */
    public function locationHandler()
    {
        return $this->locationHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler
     */
    public function objectStateHandler()
    {
        return $this->objectStateHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\User\Handler
     */
    public function userHandler()
    {
        return $this->userHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Section\Handler
     */
    public function sectionHandler()
    {
        return $this->sectionHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler
     */
    public function trashHandler()
    {
        return $this->trashHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\UrlAlias\Handler
     */
    public function urlAliasHandler()
    {
        return $this->urlAliasHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\UrlWildcard\Handler
     */
    public function urlWildcardHandler()
    {
        return $this->urlWildcardHandler;
    }

    /**
     * @return TransactionHandler
     */
    public function transactionHandler()
    {
        return $this->transactionHandler;
    }

    public function settingHandler(): SPISettingHandler
    {
        return $this->settingHandler;
    }

    /**
     * @return CacheUrlHandler
     */
    public function urlHandler()
    {
        return $this->urlHandler;
    }

    /**
     * @return CacheBookmarkHandler
     */
    public function bookmarkHandler()
    {
        return $this->bookmarkHandler;
    }

    /**
     * @return CacheNotificationHandler
     */
    public function notificationHandler()
    {
        return $this->notificationHandler;
    }

    /**
     * @return CacheUserPreferenceHandler
     */
    public function userPreferenceHandler()
    {
        return $this->userPreferenceHandler;
    }
}
