<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\URL;

use Ibexa\Contracts\Core\Persistence\URL\URL;
use Ibexa\Contracts\Core\Persistence\URL\URLUpdateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\URL\URLQuery;
use Ibexa\Core\Persistence\Legacy\URL\Gateway;
use Ibexa\Core\Persistence\Legacy\URL\Handler;
use Ibexa\Core\Persistence\Legacy\URL\Mapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    private const string TEST_URL = 'http://ibexa.co';

    private Gateway & MockObject $gateway;

    private Mapper & MockObject $mapper;

    private Handler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = $this->createMock(Gateway::class);
        $this->mapper = $this->createMock(Mapper::class);
        $this->handler = new Handler($this->gateway, $this->mapper);
    }

    public function testUpdateUrl(): void
    {
        $urlUpdateStruct = new URLUpdateStruct();
        $url = $this->getUrl();

        $this->mapper
            ->expects(self::once())
            ->method('createURLFromUpdateStruct')
            ->with($urlUpdateStruct)
            ->willReturn($url);

        $this->gateway
            ->expects(self::once())
            ->method('updateUrl')
            ->with($url);

        self::assertEquals($url, $this->handler->updateUrl($url->id, $urlUpdateStruct));
    }

    public function testFind(): void
    {
        $query = new URLQuery();
        $query->filter = new Criterion\Validity(true);
        $query->sortClauses = [
            new SortClause\Id(),
        ];
        $query->offset = 2;
        $query->limit = 10;

        $results = [
            'count' => 1,
            'rows' => [
                [
                    'id' => 1,
                    'url' => self::TEST_URL,
                ],
            ],
        ];

        $expected = [
            'count' => 1,
            'items' => [
                $this->getUrl(),
            ],
        ];

        $this->gateway
            ->expects(self::once())
            ->method('find')
            ->with($query->filter, $query->offset, $query->limit, $query->sortClauses, $query->performCount)
            ->willReturn($results);

        $this->mapper
            ->expects(self::once())
            ->method('extractURLsFromRows')
            ->with($results['rows'])
            ->willReturn($expected['items']);

        self::assertEquals($expected, $this->handler->find($query));
    }

    public function testLoadByIdWithoutUrlData(): void
    {
        $this->expectException(NotFoundException::class);

        $id = 1;

        $this->gateway
            ->expects(self::once())
            ->method('loadUrlData')
            ->with($id)
            ->willReturn([]);

        $this->mapper
            ->expects(self::once())
            ->method('extractURLsFromRows')
            ->with([])
            ->willReturn([]);

        $this->handler->loadById($id);
    }

    public function testLoadByIdWithUrlData(): void
    {
        $url = $this->getUrl();

        $this->gateway
            ->expects(self::once())
            ->method('loadUrlData')
            ->with($url->id)
            ->willReturn([$url]);

        $this->mapper
            ->expects(self::once())
            ->method('extractURLsFromRows')
            ->with([$url])
            ->willReturn([$url]);

        self::assertEquals($url, $this->handler->loadById($url->id));
    }

    public function testLoadByUrlWithoutUrlData(): void
    {
        $this->expectException(NotFoundException::class);

        $url = self::TEST_URL;

        $this->gateway
            ->expects(self::once())
            ->method('loadUrlDataByUrl')
            ->with($url)
            ->willReturn([]);

        $this->mapper
            ->expects(self::once())
            ->method('extractURLsFromRows')
            ->with([])
            ->willReturn([]);

        $this->handler->loadByUrl($url);
    }

    public function testLoadByUrlWithUrlData(): void
    {
        $url = $this->getUrl();

        $this->gateway
            ->expects(self::once())
            ->method('loadUrlDataByUrl')
            ->with($url->url)
            ->willReturn([$url]);

        $this->mapper
            ->expects(self::once())
            ->method('extractURLsFromRows')
            ->with([$url])
            ->willReturn([$url]);

        self::assertEquals($url, $this->handler->loadByUrl($url->url));
    }

    public function testFindUsages(): void
    {
        $url = $this->getUrl();
        $ids = [1, 2, 3];

        $this->gateway
            ->expects(self::once())
            ->method('findUsages')
            ->with($url->id)
            ->will(self::returnValue($ids));

        self::assertEquals($ids, $this->handler->findUsages($url->id));
    }

    private function getUrl(): URL
    {
        $url = new URL();
        $url->id = 1;
        $url->url = self::TEST_URL;

        return $url;
    }
}
