<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\BackgroundIndexingTerminateListener;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\HttpKernel\KernelEvents;

class BackgroundIndexingTerminateListenerTest extends TestCase
{
    /** @var \Ibexa\Bundle\Core\EventListener\BackgroundIndexingTerminateListener */
    protected $listener;

    /** @var \Ibexa\Contracts\Core\Persistence\Handler|\PHPUnit\Framework\MockObject\MockObject */
    protected $persistenceMock;

    /** @var \Ibexa\Contracts\Core\Search\Handler|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persistenceMock = $this->createMock(PersistenceHandler::class);
        $this->searchMock = $this->createMock(SearchHandler::class);
        $this->listener = new BackgroundIndexingTerminateListener(
            $this->persistenceMock,
            $this->searchMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->persistenceMock, $this->searchMock, $this->listener);
        parent::tearDown();
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                KernelEvents::TERMINATE => 'reindex',
                KernelEvents::EXCEPTION => 'reindex',
                ConsoleEvents::TERMINATE => 'reindex',
            ],
            BackgroundIndexingTerminateListener::getSubscribedEvents()
        );
    }

    public static function indexingProvider()
    {
        $info = new ContentInfo(['id' => 33]);
        $location = new Location(['id' => 44, 'contentId' => 33]);

        return [
            [[$location]],
            [[$location], self::createStub(LoggerInterface::class)],
            [[$info]],
            [[$info], self::createStub(LoggerInterface::class)],
            [null],
            [null, self::createStub(LoggerInterface::class)],
            [[$location, $info]],
            [[$info, $location], self::createStub(LoggerInterface::class)],
        ];
    }

    /**
     * @param array|null $value
     * @param \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject|null $logger
     */
    #[DataProvider('indexingProvider')]
    public function testIndexing(?array $values = null, $logger = null)
    {
        $contentHandlerMock = $this->createMock(Content\Handler::class);
        $this->persistenceMock
            ->expects(self::once())
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        if ($values) {
            $contentHandlerMock
                ->expects(self::once())
                ->method('loadContentInfo')
                ->with(33)
                ->willReturn(new ContentInfo(['id' => 33, 'currentVersionNo' => 2, 'status' => ContentInfo::STATUS_PUBLISHED]));

            $contentHandlerMock
                ->expects(self::once())
                ->method('load')
                ->with(33, 2)
                ->willReturn(new Content());

            $this->searchMock
                ->expects(self::once())
                ->method('indexContent')
                ->with(self::isInstanceOf(Content::class));

            $this->searchMock->expects(self::never())->method('indexLocation');
            $this->searchMock->expects(self::never())->method('deleteContent');
            $this->searchMock->expects(self::never())->method('deleteLocation');
        } else {
            $contentHandlerMock->expects(self::never())->method(self::anything());
            $this->searchMock->expects(self::never())->method(self::anything());
        }

        foreach ((array) $values as $value) {
            if ($value instanceof Location) {
                $this->listener->registerLocation($value);
            } elseif ($value instanceof ContentInfo) {
                $this->listener->registerContent($value);
            }
        }

        if ($logger) {
            $this->listener->setLogger($logger);

            if ($values) {
                $logger->expects(self::once())
                    ->method('warning')
                    ->with(self::isString());
            } else {
                $logger->expects(self::never())
                    ->method('warning');
            }
        }

        $this->listener->reindex();
    }

    public static function indexDeleteProvider()
    {
        $location = new Location(['id' => 44, 'contentId' => 33]);
        $info = new ContentInfo(['id' => 33, 'currentVersionNo' => 2, 'status' => ContentInfo::STATUS_PUBLISHED]);

        $infoReturn = $info;
        $infoReturnUnPublished = new ContentInfo(['id' => 33, 'currentVersionNo' => 2]);
        $returnThrow = new NotFoundException('content', '33');

        return [
            [$location, $infoReturn, $returnThrow],
            [$location, $returnThrow],
            [$location, $infoReturnUnPublished],

            [$info, $infoReturn, $returnThrow],
            [$info, $returnThrow],
            [$info, $infoReturnUnPublished],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\ContentInfo|\Ibexa\Contracts\Core\Persistence\Content\Location $value
     * @param \Ibexa\Contracts\Core\Persistence\Content\ContentInfo|\Throwable $infoReturn
     * @param \Ibexa\Contracts\Core\Persistence\Content|\Throwable|null $contentReturn
     */
    #[DataProvider('indexDeleteProvider')]
    public function testIndexDelete($value, $infoReturn, $contentReturn = null)
    {
        $contentHandlerMock = $this->createMock(Content\Handler::class);
        $this->persistenceMock
            ->expects(self::once())
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $loadContentInfoExpectation = $contentHandlerMock
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with(33);
        if ($infoReturn instanceof \Throwable) {
            $loadContentInfoExpectation->willThrowException($infoReturn);
        } else {
            $loadContentInfoExpectation->willReturn($infoReturn);
        }

        if ($contentReturn) {
            $loadExpectation = $contentHandlerMock
                ->expects(self::once())
                ->method('load')
                ->with(33, 2);
            if ($contentReturn instanceof \Throwable) {
                $loadExpectation->willThrowException($contentReturn);
            } else {
                $loadExpectation->willReturn($contentReturn);
            }
        } else {
            $contentHandlerMock
                ->expects(self::never())
                ->method('load');
        }

        $this->searchMock->expects(self::never())->method('indexContent');
        $this->searchMock->expects(self::never())->method('indexLocation');

        if ($value instanceof Location) {
            $contentId = $value->contentId;
            $locationId = $value->id;
            $this->listener->registerLocation($value);
        } else {
            $contentId = $value->id;
            $locationId = $value->mainLocationId;
            $this->listener->registerContent($value);
        }

        $this->searchMock
            ->expects(self::once())
            ->method('deleteContent')
            ->with($contentId);

        if ($locationId) {
            $this->searchMock
                ->expects(self::once())
                ->method('deleteLocation')
                ->with($locationId);
        } else {
            $this->searchMock->expects(self::never())->method('deleteLocation');
        }

        $this->listener->reindex();
    }
}
