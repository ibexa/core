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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler\DoctrineDatabase
 */
class DoctrineDatabaseTest extends TestCase
{
    protected Gateway & MockObject $gatewayMock;

    protected ContentUpdater & MockObject $contentUpdaterMock;

    public function testUpdateContentObjects(): void
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

    public function testDeleteOldType(): void
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

        $handler->deleteOldType($types['from']);
    }

    public function testPublishNewType(): void
    {
        $handler = $this->getUpdateHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('publishTypeAndFields')
            ->with(self::equalTo(23), self::equalTo(1), self::equalTo(0));

        $types = $this->getTypeFixtures();

        $handler->publishNewType($types['to'], 0);
    }

    /**
     * Returns an array with 'from' and 'to' types.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type[]
     */
    protected function getTypeFixtures(): array
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

    protected function getUpdateHandler(): DoctrineDatabase
    {
        return new DoctrineDatabase($this->getGatewayMock());
    }

    protected function getGatewayMock(): Gateway & MockObject
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->gatewayMock;
    }

    protected function getContentUpdaterMock(): ContentUpdater & MockObject
    {
        if (!isset($this->contentUpdaterMock)) {
            $this->contentUpdaterMock = $this->createMock(ContentUpdater::class);
        }

        return $this->contentUpdaterMock;
    }
}
