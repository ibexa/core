<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\MatchNone;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\BlockingLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Limitation\BlockingLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Test Case for LimitationType.
 */
class BlockingLimitationTypeTest extends Base
{
    /**
     * @return \Ibexa\Core\Limitation\BlockingLimitationType
     */
    public function testConstruct()
    {
        return new BlockingLimitationType('Test');
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new BlockingLimitation('Test', [])],
            [new BlockingLimitation('FunctionList', [])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\BlockingLimitation $limitation
     * @param \Ibexa\Core\Limitation\BlockingLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(BlockingLimitation $limitation, BlockingLimitationType $limitationType)
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
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\BlockingLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, BlockingLimitationType $limitationType)
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
            [new BlockingLimitation('Test', ['limitationValues' => ['ezjscore::call']])],
            [new BlockingLimitation('Test', ['limitationValues' => ['ezjscore::call', 'my::call']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\BlockingLimitation $limitation
     */
    #[DataProvider('providerForTestValidatePass')]
    public function testValidatePass(BlockingLimitation $limitation)
    {
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
            [new BlockingLimitation('Test', []), 1],
            [new BlockingLimitation('Test', ['limitationValues' => [0]]), 0],
            [new BlockingLimitation('Test', ['limitationValues' => [0, PHP_INT_MAX]]), 0],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\BlockingLimitation $limitation
     * @param int $errorCount
     */
    #[DataProvider('providerForTestValidateError')]
    public function testValidateError(BlockingLimitation $limitation, $errorCount)
    {
        $this->getPersistenceMock()
                ->expects(self::never())
                ->method(self::anything());

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertCount($errorCount, $validationErrors);
    }

    /**
     * @param \Ibexa\Core\Limitation\BlockingLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testBuildValue(BlockingLimitationType $limitationType)
    {
        $expected = ['test', 'test' => 9];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(BlockingLimitation::class, $value);
        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluate()
    {
        return [
            // ContentInfo, no access
            [
                'limitation' => new BlockingLimitation('Test', []),
                'object' => new ContentInfo(),
                'targets' => [],
            ],
            // ContentInfo, no access
            [
                'limitation' => new BlockingLimitation('Test', ['limitationValues' => [2]]),
                'object' => new ContentInfo(),
                'targets' => [],
            ],
            // ContentInfo, with access
            [
                'limitation' => new BlockingLimitation('Test', ['limitationValues' => [66]]),
                'object' => new ContentInfo(['contentTypeId' => 66]),
                'targets' => [],
            ],
            // ContentCreateStruct, no access
            [
                'limitation' => new BlockingLimitation('Test', ['limitationValues' => [2]]),
                'object' => new ContentCreateStruct(['contentType' => self::createContentTypeWithId(22)]),
                'targets' => [],
            ],
            // ContentCreateStruct, with access
            [
                'limitation' => new BlockingLimitation('Test', ['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(['contentType' => self::createContentTypeWithId(43)]),
                'targets' => [],
            ],
        ];
    }

    private static function createContentTypeWithId(int $id): ContentType
    {
        return new ContentType(['id' => $id]);
    }

    #[DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        BlockingLimitation $limitation,
        ValueObject $object,
        array $targets
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

        self::assertFalse($value);
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
     * @param \Ibexa\Core\Limitation\BlockingLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterion(BlockingLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new BlockingLimitation('Test', []),
            $this->getUserMock()
        );

        self::assertInstanceOf(MatchNone::class, $criterion);
    }

    /**
     * @param \Ibexa\Core\Limitation\BlockingLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testValueSchema(BlockingLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        self::assertEquals(
            [],
            $limitationType->valueSchema()
        );
    }
}
