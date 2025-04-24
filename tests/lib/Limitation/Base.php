<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class Base extends TestCase
{
    private PersistenceHandler & MockObject $persistenceHandlerMock;

    private APIUser & MockObject $userMock;

    public function getPersistenceMock(): PersistenceHandler & MockObject
    {
        return $this->persistenceHandlerMock ?? ($this->persistenceHandlerMock = $this->createMock(
            PersistenceHandler::class
        ));
    }

    public function getUserMock(): APIUser & MockObject
    {
        return $this->userMock ?? ($this->userMock = $this->createMock(APIUser::class));
    }

    /**
     * unset properties.
     */
    protected function tearDown(): void
    {
        if (isset($this->persistenceHandlerMock)) {
            unset($this->persistenceHandlerMock);
        }

        if (isset($this->userMock)) {
            unset($this->userMock);
        }

        parent::tearDown();
    }
}
