<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\BooleanField;
use Ibexa\Contracts\Core\Search\FieldType\FloatField;
use Ibexa\Core\Search\Common\FieldValueMapper\Aggregate;
use Ibexa\Core\Search\Common\FieldValueMapper\BooleanMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Search\Common\FieldValueMapper\Aggregate
 */
final class AggregateTest extends TestCase
{
    private const MAPPED_VALUE = true;

    /** @var Aggregate */
    private $aggregateMapper;

    public function setUp(): void
    {
        $this->aggregateMapper = new Aggregate();
    }

    public function testMapUsingSimpleMapper(): void
    {
        $booleanMapperMock = $this->createMock(BooleanMapper::class);
        $this->aggregateMapper->addMapper($booleanMapperMock, BooleanField::class);

        $booleanField = new BooleanField();
        $searchFieldMock = $this->createMock(Field::class);
        $searchFieldMock
            ->method('getType')
            ->willReturn($booleanField);
        $booleanMapperMock
            ->method('map')
            ->with($searchFieldMock)
            ->willReturn(self::MAPPED_VALUE);

        self::assertSame(self::MAPPED_VALUE, $this->aggregateMapper->map($searchFieldMock));
    }

    public function testMapUsingCanMap(): void
    {
        $booleanMapper = new BooleanMapper();
        $this->aggregateMapper->addMapper($booleanMapper);

        $booleanField = new BooleanField();
        $searchFieldMock = $this->createMock(Field::class);
        $searchFieldMock
            ->method('getType')
            ->willReturn($booleanField);
        $searchFieldMock
            ->method('getValue')
            ->willReturn(self::MAPPED_VALUE);

        self::assertSame(self::MAPPED_VALUE, $this->aggregateMapper->map($searchFieldMock));
    }

    public function testMapThrowsNotImplementedException(): void
    {
        $booleanMapperMock = $this->createMock(BooleanMapper::class);
        $this->aggregateMapper->addMapper($booleanMapperMock);

        $floatFieldMock = $this->createMock(FloatField::class);
        $searchFieldMock = $this->createMock(Field::class);
        $searchFieldMock
            ->method('getType')
            ->willReturn($floatFieldMock);
        $booleanMapperMock
            ->method('canMap')
            ->willReturn(false);

        $this->expectException(NotImplementedException::class);
        $this->expectExceptionMessage('No mapper available for: ' . get_class($floatFieldMock));
        $this->aggregateMapper->map($searchFieldMock);
    }
}
