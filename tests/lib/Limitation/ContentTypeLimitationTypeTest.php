<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Target\Builder\VersionBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\ContentTypeLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

/**
 * Test Case for LimitationType.
 */
class ContentTypeLimitationTypeTest extends Base
{
    private SPIHandler & MockObject $contentTypeHandlerMock;

    /**
     * Setup Location Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->contentTypeHandlerMock = $this->createMock(SPIHandler::class);
    }

    /**
     * Tear down Location Handler mock.
     */
    protected function tearDown(): void
    {
        unset($this->contentTypeHandlerMock);
        parent::tearDown();
    }

    /**
     * @return \Ibexa\Core\Limitation\ContentTypeLimitationType
     */
    public function testConstruct(): ContentTypeLimitationType
    {
        return new ContentTypeLimitationType($this->getPersistenceMock());
    }

    /**
     * @phpstan-return list<array{\Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation}>
     */
    public function providerForTestAcceptValue(): array
    {
        return [
            [new ContentTypeLimitation()],
            [new ContentTypeLimitation([])],
            [new ContentTypeLimitation(['limitationValues' => [0, PHP_INT_MAX, '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValue
     *
     * @depends      testConstruct
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation $limitation
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testAcceptValue(ContentTypeLimitation $limitation, ContentTypeLimitationType $limitationType): void
    {
        $limitationType->acceptValue($limitation);
    }

    /**
     * @phpstan-return list<array{\Ibexa\Contracts\Core\Repository\Values\User\Limitation}>
     */
    public function providerForTestAcceptValueException(): array
    {
        return [
            [new ObjectStateLimitation()],
            [new ContentTypeLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     *
     * @depends testConstruct
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    public function testAcceptValueException(Limitation $limitation, ContentTypeLimitationType $limitationType): void
    {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @phpstan-return list<array{\Ibexa\Contracts\Core\Repository\Values\User\Limitation}>
     */
    public function providerForTestValidatePass(): array
    {
        return [
            [new ContentTypeLimitation()],
            [new ContentTypeLimitation([])],
            [new ContentTypeLimitation(['limitationValues' => [2]])],
        ];
    }

    /**
     * @dataProvider providerForTestValidatePass
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation $limitation
     */
    public function testValidatePass(ContentTypeLimitation $limitation): void
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->method('contentTypeHandler')
                ->willReturn($this->contentTypeHandlerMock);

            foreach ($limitation->limitationValues as $key => $value) {
                $this->contentTypeHandlerMock
                    ->expects(self::at($key))
                    ->method('load')
                    ->with($value);
            }
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertEmpty($validationErrors);
    }

    /**
     * @phpstan-return list<array{\Ibexa\Contracts\Core\Repository\Values\User\Limitation}>
     */
    public function providerForTestValidateError(): array
    {
        return [
            [new ContentTypeLimitation(), 0],
            [new ContentTypeLimitation(['limitationValues' => [0]]), 1],
            [new ContentTypeLimitation(['limitationValues' => [0, PHP_INT_MAX]]), 2],
        ];
    }

    /**
     * @dataProvider providerForTestValidateError
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation $limitation
     * @param int $errorCount
     */
    public function testValidateError(ContentTypeLimitation $limitation, int $errorCount): void
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->method('contentTypeHandler')
                ->willReturn($this->contentTypeHandlerMock);

            foreach ($limitation->limitationValues as $key => $value) {
                $this->contentTypeHandlerMock
                    ->expects(self::at($key))
                    ->method('load')
                    ->with($value)
                    ->will(self::throwException(new NotFoundException('contentType', $value)));
            }
        } else {
            $this->getPersistenceMock()
                ->expects(self::never())
                ->method(self::anything());
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertCount($errorCount, $validationErrors);
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    public function testBuildValue(ContentTypeLimitationType $limitationType): void
    {
        $expected = ['test', 'test' => 9];
        $value = $limitationType->buildValue($expected);

        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    /**
     * @phpstan-return list<array{
     *     limitation: \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation,
     *     object: \Ibexa\Contracts\Core\Repository\Values\ValueObject,
     *     targets: array<\Ibexa\Contracts\Core\Limitation\Target>,
     *     expected: bool
     * }>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function providerForTestEvaluate(): array
    {
        // Mocks for testing Content & VersionInfo objects should only be used once because of expect rules.
        $contentMock = $this->createMock(APIContent::class);
        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $contentMock
            ->expects(self::once())
            ->method('getVersionInfo')
            ->willReturn($versionInfoMock);

        $versionInfoMock
            ->expects(self::once())
            ->method('getContentInfo')
            ->willReturn(new ContentInfo(['contentTypeId' => 66]));

        $versionInfoMock2 = $this->createMock(APIVersionInfo::class);

        $versionInfoMock2
            ->expects(self::once())
            ->method('getContentInfo')
            ->willReturn(new ContentInfo(['contentTypeId' => 66]));

        return [
            // ContentInfo, no access
            [
                'limitation' => new ContentTypeLimitation(),
                'object' => new ContentInfo(),
                'targets' => [],
                'expected' => false,
            ],
            // ContentInfo, no access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(),
                'targets' => [],
                'expected' => false,
            ],
            // ContentInfo, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [66]]),
                'object' => new ContentInfo(['contentTypeId' => 66]),
                'targets' => [],
                'expected' => true,
            ],
            // Content, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [66]]),
                'object' => $contentMock,
                'targets' => [],
                'expected' => true,
            ],
            // VersionInfo, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [66]]),
                'object' => $versionInfoMock2,
                'targets' => [],
                'expected' => true,
            ],
            // ContentCreateStruct, no access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2]]),
                'object' => new ContentCreateStruct(['contentType' => ((object)['id' => 22])]),
                'targets' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => ((object)['id' => 43])]),
                'targets' => [],
                'expected' => true,
            ],
            // ContentType intention test, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => (object)['id' => 22]]),
                'targets' => [(new VersionBuilder())->createFromAnyContentTypeOf([43])->build()],
                'expected' => true,
            ],
            // ContentType intention test, no access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => (object)['id' => 22]]),
                'targets' => [(new VersionBuilder())->createFromAnyContentTypeOf([23])->build()],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluate
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject[] $targets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testEvaluate(
        ContentTypeLimitation $limitation,
        ValueObject $object,
        array $targets,
        bool $expected
    ): void {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $persistenceMock = $this->getPersistenceMock();
        $persistenceMock
            ->expects(self::never())
            ->method(self::anything());

        $value = $limitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );

        self::assertIsBool($value);
        self::assertEquals($expected, $value);
    }

    /**
     * @phpstan-return list<array{
     *      limitation: \Ibexa\Contracts\Core\Repository\Values\User\Limitation,
     *      object: \Ibexa\Contracts\Core\Repository\Values\ValueObject,
     *      targets: array<\Ibexa\Contracts\Core\Repository\Values\ValueObject>,
     * }>
     */
    public function providerForTestEvaluateInvalidArgument(): array
    {
        return [
            // invalid limitation
            [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new Location()],
            ],
            // invalid object
            [
                'limitation' => new ContentTypeLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new Location()],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluateInvalidArgument
     *
     * @param array<\Ibexa\Contracts\Core\Repository\Values\ValueObject> $targets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        array $targets
    ): void {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $persistenceMock = $this->getPersistenceMock();
        $persistenceMock
            ->expects(self::never())
            ->method(self::anything());

        $this->expectException(InvalidArgumentException::class);
        $limitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    public function testGetCriterionInvalidValue(ContentTypeLimitationType $limitationType): void
    {
        $this->expectException(RuntimeException::class);

        $limitationType->getCriterion(
            new ContentTypeLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    public function testGetCriterionSingleValue(ContentTypeLimitationType $limitationType): void
    {
        $criterion = $limitationType->getCriterion(
            new ContentTypeLimitation(['limitationValues' => [9]]),
            $this->getUserMock()
        );

        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals([9], $criterion->value);
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    public function testGetCriterionMultipleValues(ContentTypeLimitationType $limitationType): void
    {
        $criterion = $limitationType->getCriterion(
            new ContentTypeLimitation(['limitationValues' => [9, 55]]),
            $this->getUserMock()
        );

        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals([9, 55], $criterion->value);
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    public function testValueSchema(ContentTypeLimitationType $limitationType): void
    {
        $this->expectException(NotImplementedException::class);
        $limitationType->valueSchema();
    }
}
