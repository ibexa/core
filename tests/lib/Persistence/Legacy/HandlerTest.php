<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Persistence\Legacy;

use Ibexa\Contracts\Core\Container;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as SPILanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as SPILocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Section\Handler as SPISectionHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\UrlAlias\Handler as SPIUrlAliasHandler;
use Ibexa\Contracts\Core\Persistence\TransactionHandler as SPITransactionHandler;
use Ibexa\Contracts\Core\Persistence\User\Handler as SPIUserHandler;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Base\ServiceContainer;
use Ibexa\Core\Persistence\Legacy\Content\Handler as ContentHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Handler as LocationHandler;
use Ibexa\Core\Persistence\Legacy\Content\Section\Handler as SectionHandler;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Handler as UrlAliasHandler;
use Ibexa\Core\Persistence\Legacy\Handler;
use Ibexa\Core\Persistence\Legacy\TransactionHandler;
use Ibexa\Core\Persistence\Legacy\User\Handler as UserHandler;
use Ibexa\Tests\Integration\Core\LegacyTestContainerBuilder;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Handler::contentHandler
 */
class HandlerTest extends TestCase
{
    public function testContentHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $contentHandler = $handler->contentHandler();

        $this->assertInstanceOf(
            SPIContentHandler::class,
            $contentHandler
        );
        $this->assertInstanceOf(
            ContentHandler::class,
            $contentHandler
        );
    }

    public function testContentHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->contentHandler(),
            $handler->contentHandler()
        );
    }

    public function testContentTypeHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $contentTypeHandler = $handler->contentTypeHandler();

        $this->assertInstanceOf(
            SPIContentTypeHandler::class,
            $contentTypeHandler
        );
    }

    public function testContentLanguageHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $contentLanguageHandler = $handler->contentLanguageHandler();

        $this->assertInstanceOf(
            SPILanguageHandler::class,
            $contentLanguageHandler
        );
    }

    public function testContentTypeHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->contentTypeHandler(),
            $handler->contentTypeHandler()
        );
    }

    public function testLocationHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $locationHandler = $handler->locationHandler();

        $this->assertInstanceOf(
            SPILocationHandler::class,
            $locationHandler
        );
        $this->assertInstanceOf(
            LocationHandler::class,
            $locationHandler
        );
    }

    public function testLocationHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->locationHandler(),
            $handler->locationHandler()
        );
    }

    public function testUserHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $userHandler = $handler->userHandler();

        $this->assertInstanceOf(
            SPIUserHandler::class,
            $userHandler
        );
        $this->assertInstanceOf(
            UserHandler::class,
            $userHandler
        );
    }

    public function testUserHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->userHandler(),
            $handler->userHandler()
        );
    }

    public function testSectionHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $sectionHandler = $handler->sectionHandler();

        $this->assertInstanceOf(
            SPISectionHandler::class,
            $sectionHandler
        );
        $this->assertInstanceOf(
            SectionHandler::class,
            $sectionHandler
        );
    }

    public function testSectionHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->sectionHandler(),
            $handler->sectionHandler()
        );
    }

    public function testUrlAliasHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $urlAliasHandler = $handler->urlAliasHandler();

        $this->assertInstanceOf(
            SPIUrlAliasHandler::class,
            $urlAliasHandler
        );
        $this->assertInstanceOf(
            UrlAliasHandler::class,
            $urlAliasHandler
        );
    }

    public function testUrlAliasHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->urlAliasHandler(),
            $handler->urlAliasHandler()
        );
    }

    public function testNotificationHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->notificationHandler(),
            $handler->notificationHandler()
        );
    }

    public function testTransactionHandler(): void
    {
        $handler = $this->getHandlerFixture();
        $transactionHandler = $handler->transactionHandler();

        $this->assertInstanceOf(
            SPITransactionHandler::class,
            $transactionHandler
        );
        $this->assertInstanceOf(
            TransactionHandler::class,
            $transactionHandler
        );
    }

    public function testTransactionHandlerTwice(): void
    {
        $handler = $this->getHandlerFixture();

        $this->assertSame(
            $handler->transactionHandler(),
            $handler->transactionHandler()
        );
    }

    protected static Handler $legacyHandler;

    protected function getHandlerFixture(): Handler
    {
        return self::$legacyHandler ??= $this->getContainer()->get(Handler::class);
    }

    protected static Container $container;

    protected function getContainer(): Container
    {
        if (!isset(self::$container)) {
            $installDir = self::getInstallationDir();

            $containerBuilder = new LegacyTestContainerBuilder();

            $loader = $containerBuilder->getCoreLoader();
            $loader->load('search_engines/legacy.yml');
            // tests/integration/Core/Resources/settings/integration_legacy.yml
            $loader->load('integration_legacy.yml');

            $containerBuilder->setParameter(
                'languages',
                ['eng-US', 'eng-GB']
            );
            $containerBuilder->setParameter(
                'ibexa.persistence.legacy.dsn',
                $this->getDsn()
            );

            self::$container = new ServiceContainer(
                $containerBuilder,
                $installDir,
                Legacy::getCacheDir(),
                true,
                true
            );
        }

        return self::$container;
    }
}

class_alias(HandlerTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\HandlerTest');
