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
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\ContentTypeLimitationType;
use Ibexa\Core\Repository\Values\Content\Content as CoreContent;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo as CoreVersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Test Case for LimitationType.
 */
class ContentTypeLimitationTypeTest extends Base
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $contentTypeHandlerMock;

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
    public function testConstruct()
    {
        return new ContentTypeLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new ContentTypeLimitation()],
            [new ContentTypeLimitation([])],
            [new ContentTypeLimitation(['limitationValues' => [0, PHP_INT_MAX, '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation $limitation
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(ContentTypeLimitation $limitation, ContentTypeLimitationType $limitationType)
    {
        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValueException()
    {
        return [
            [new ObjectStateLimitation()],
            [new ContentTypeLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, ContentTypeLimitationType $limitationType)
    {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestValidatePass()
    {
        return [
            [new ContentTypeLimitation()],
            [new ContentTypeLimitation([])],
            [new ContentTypeLimitation(['limitationValues' => [2]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation $limitation
     */
    #[DataProvider('providerForTestValidatePass')]
    public function testValidatePass(ContentTypeLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('contentTypeHandler')
                ->will(self::returnValue($this->contentTypeHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->contentTypeHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue) use ($matcher, $limitationValues): void {
                    self::assertSame($limitationValues[$matcher->numberOfInvocations() - 1], $actualValue);
                });
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertEmpty($validationErrors);
    }

    /**
     * @return array
     */
    public static function providerForTestValidateError()
    {
        return [
            [new ContentTypeLimitation(), 0],
            [new ContentTypeLimitation(['limitationValues' => [0]]), 1],
            [new ContentTypeLimitation(['limitationValues' => [0, PHP_INT_MAX]]), 2],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation $limitation
     * @param int $errorCount
     */
    #[DataProvider('providerForTestValidateError')]
    public function testValidateError(ContentTypeLimitation $limitation, $errorCount)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('contentTypeHandler')
                ->will(self::returnValue($this->contentTypeHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->contentTypeHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue) use ($matcher, $limitationValues): void {
                    $value = $limitationValues[$matcher->numberOfInvocations() - 1];
                    self::assertSame($value, $actualValue);

                    throw new NotFoundException('contentType', $value);
                });
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
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testBuildValue(ContentTypeLimitationType $limitationType)
    {
        $expected = ['test', 'test' => 9];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(ContentTypeLimitation::class, $value);
        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluate()
    {
        $contentMock = new CoreContent([
            'versionInfo' => new CoreVersionInfo([
                'contentInfo' => new ContentInfo(['contentTypeId' => 66]),
            ]),
        ]);

        $versionInfoMock2 = new CoreVersionInfo([
            'contentInfo' => new ContentInfo(['contentTypeId' => 66]),
        ]);

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
                'object' => new ContentInfo(['contentTypeId' => 23]),
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
                'object' => new ContentCreateStruct(['contentType' => new ContentType(['id' => 22])]),
                'targets' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => new ContentType(['id' => 43])]),
                'targets' => [],
                'expected' => true,
            ],
            // ContentType intention test, with access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => new ContentType(['id' => 22])]),
                'targets' => [(new VersionBuilder())->createFromAnyContentTypeOf([43])->build()],
                'expected' => true,
            ],
            // ContentType intention test, no access
            [
                'limitation' => new ContentTypeLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => new ContentType(['id' => 22])]),
                'targets' => [(new VersionBuilder())->createFromAnyContentTypeOf([23])->build()],
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        ContentTypeLimitation $limitation,
        ValueObject $object,
        array $targets,
        $expected
    ) {
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
     * @return array
     */
    public static function providerForTestEvaluateInvalidArgument()
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

    #[DataProvider('providerForTestEvaluateInvalidArgument')]
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        array $targets
    ) {
        $this->expectException(InvalidArgumentException::class);

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

        $v = $limitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );
        var_dump($v); // intentional, debug in case no exception above
    }

    /**
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterionInvalidValue(ContentTypeLimitationType $limitationType)
    {
        $this->expectException(\RuntimeException::class);

        $limitationType->getCriterion(
            new ContentTypeLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterionSingleValue(ContentTypeLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new ContentTypeLimitation(['limitationValues' => [9]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(ContentTypeId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals([9], $criterion->value);
    }

    /**
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterionMultipleValues(ContentTypeLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new ContentTypeLimitation(['limitationValues' => [9, 55]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(ContentTypeId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals([9, 55], $criterion->value);
    }

    /**
     * @param \Ibexa\Core\Limitation\ContentTypeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testValueSchema(ContentTypeLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        self::assertEquals(
            [],
            $limitationType->valueSchema()
        );
    }
}
