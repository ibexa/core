<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\Update\Handler;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler\DoctrineDatabase;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler\DoctrineDatabase
 */
class DoctrineDatabaseTest extends TestCase
{
    /**
     * Gateway mock.
     *
     * @var Gateway
     */
    protected $gatewayMock;

    /**
     * Content Updater mock.
     *
     * @var ContentUpdater
     */
    protected $contentUpdaterMock;

    public function testUpdateContentObjects()
    {
        $handler = $this->getUpdateHandler();

        $updaterMock = $this->getContentUpdaterMock();

        $updaterMock->expects(self::never())
            ->method('determineActions');

        $updaterMock->expects(self::never())
            ->method('applyUpdates');

        $types = $this->getTypeFixtures();

        $handler->updateContentObjects($types['from'], $types['to']);
    }

    public function testDeleteOldType()
    {
        $handler = $this->getUpdateHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('delete')
            ->with(
                self::equalTo(23),
                self::equalTo(0)
            );

        $types = $this->getTypeFixtures();

        $handler->deleteOldType($types['from'], $types['to']);
    }

    public function testPublishNewType()
    {
        $handler = $this->getUpdateHandler();

        $gatewayMock = $this->getGatewayMock();
        $updaterMock = $this->getContentUpdaterMock();

        $gatewayMock->expects(self::once())
            ->method('publishTypeAndFields')
            ->with(self::equalTo(23), self::equalTo(1), self::equalTo(0));

        $types = $this->getTypeFixtures();

        $handler->publishNewType($types['to'], 0);
    }

    /**
     * Returns an array with 'from' and 'to' types.
     *
     * @return Type[]
     */
    protected function getTypeFixtures()
    {
        $types = [];

        $types['from'] = new Type();
        $types['from']->id = 23;
        $types['from']->status = Type::STATUS_DEFINED;

        $types['to'] = new Type();
        $types['to']->id = 23;
        $types['to']->status = Type::STATUS_DRAFT;

        return $types;
    }

    /**
     * Returns the Update Handler to test.
     *
     * @return DoctrineDatabase
     */
    protected function getUpdateHandler()
    {
        return new DoctrineDatabase($this->getGatewayMock());
    }

    /**
     * Returns a gateway mock.
     *
     * @return Gateway
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->gatewayMock;
    }

    /**
     * Returns a Content Updater mock.
     *
     * @return ContentUpdater
     */
    protected function getContentUpdaterMock()
    {
        if (!isset($this->contentUpdaterMock)) {
            $this->contentUpdaterMock = $this->createMock(ContentUpdater::class);
        }

        return $this->contentUpdaterMock;
    }
}
