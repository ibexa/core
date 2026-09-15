<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler as SPIHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\NewObjectStateLimitationType;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ObjectState\ObjectState;

/**
 * Test Case for LimitationType.
 */
class NewObjectStateLimitationTypeTest extends Base
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $objectStateHandlerMock;

    /**
     * Setup Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectStateHandlerMock = $this->createMock(SPIHandler::class);
    }

    /**
     * Tear down Handler mock.
     */
    protected function tearDown(): void
    {
        unset($this->objectStateHandlerMock);
        parent::tearDown();
    }

    /**
     * @return \Ibexa\Core\Limitation\NewObjectStateLimitationType
     */
    public function testConstruct()
    {
        return new NewObjectStateLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new NewObjectStateLimitation()],
            [new NewObjectStateLimitation([])],
            [new NewObjectStateLimitation(['limitationValues' => [0, PHP_INT_MAX, '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation $limitation
     * @param \Ibexa\Core\Limitation\NewObjectStateLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(NewObjectStateLimitation $limitation, NewObjectStateLimitationType $limitationType)
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
            [new NewObjectStateLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\NewObjectStateLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, NewObjectStateLimitationType $limitationType)
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
            [new NewObjectStateLimitation()],
            [new NewObjectStateLimitation([])],
            [new NewObjectStateLimitation(['limitationValues' => [2]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation $limitation
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidatePass')]
    public function testValidatePass(NewObjectStateLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('objectStateHandler')
                ->will(self::returnValue($this->objectStateHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->objectStateHandlerMock
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
            [new NewObjectStateLimitation(), 0],
            [new NewObjectStateLimitation(['limitationValues' => [0]]), 1],
            [new NewObjectStateLimitation(['limitationValues' => [0, PHP_INT_MAX]]), 2],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation $limitation
     * @param int $errorCount
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidateError')]
    public function testValidateError(NewObjectStateLimitation $limitation, $errorCount)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('objectStateHandler')
                ->will(self::returnValue($this->objectStateHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->objectStateHandlerMock
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
     * @param \Ibexa\Core\Limitation\NewObjectStateLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testBuildValue(NewObjectStateLimitationType $limitationType)
    {
        $expected = ['test', 'test' => 9];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(NewObjectStateLimitation::class, $value);
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
                'limitation' => new NewObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new ObjectState(['id' => 66])],
                'expected' => false,
            ],
            // Content, no access
            [
                'limitation' => new NewObjectStateLimitation(['limitationValues' => [2]]),
                'object' => new Content(),
                'targets' => [new ObjectState(['id' => 66])],
                'expected' => false,
            ],
            // Content, no access  (both must match!)
            [
                'limitation' => new NewObjectStateLimitation(['limitationValues' => [2, 22]]),
                'object' => new Content(),
                'targets' => [new ObjectState(['id' => 2]), new ObjectState(['id' => 66])],
                'expected' => false,
            ],
            // ContentInfo, with access
            [
                'limitation' => new NewObjectStateLimitation(['limitationValues' => [66]]),
                'object' => new ContentInfo(),
                'targets' => [new ObjectState(['id' => 66])],
                'expected' => true,
            ],
            // VersionInfo, with access
            [
                'limitation' => new NewObjectStateLimitation(['limitationValues' => [2, 66]]),
                'object' => new VersionInfo(),
                'targets' => [new ObjectState(['id' => 66])],
                'expected' => true,
            ],
            // Content, with access
            [
                'limitation' => new NewObjectStateLimitation(['limitationValues' => [2, 66]]),
                'object' => new Content(),
                'targets' => [new ObjectState(['id' => 66]), new ObjectState(['id' => 2])],
                'expected' => true,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        NewObjectStateLimitation $limitation,
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
                'limitation' => new NewObjectStateLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new Location()],
            ],
            // empty targets
            [
                'limitation' => new NewObjectStateLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluateInvalidArgument')]
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
     * @param \Ibexa\Core\Limitation\NewObjectStateLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testGetCriterion(NewObjectStateLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        $limitationType->getCriterion(
            new NewObjectStateLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\NewObjectStateLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testValueSchema(NewObjectStateLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        self::assertEquals(
            [],
            $limitationType->valueSchema()
        );
    }
}
