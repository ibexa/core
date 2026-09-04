<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Command\Role;

use Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Core\Repository\Values\User\Policy;
use Ibexa\Core\Repository\Values\User\Role;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues
 */
final class DanglingLimitationValuesTest extends TestCase
{
    /**
     * @param array<mixed> $limitationValues
     * @param array<array{key: string|null, value: mixed}> $danglingValues
     * @param array<mixed> $expectedPrunedValues
     *
     * @dataProvider providerForTestGetPrunedValues
     */
    public function testGetPrunedValues(
        array $limitationValues,
        array $danglingValues,
        array $expectedPrunedValues,
        bool $expectedEmptiesLimitation
    ): void {
        $finding = $this->buildFinding($limitationValues, $danglingValues);

        self::assertSame($expectedPrunedValues, $finding->getPrunedValues());
        self::assertSame($expectedEmptiesLimitation, $finding->emptiesLimitation());
    }

    /**
     * @return iterable<string, array{array<mixed>, array<array{key: string|null, value: mixed}>, array<mixed>, bool}>
     */
    public function providerForTestGetPrunedValues(): iterable
    {
        yield 'plain list, one of two values' => [
            ['4', '8'],
            [['key' => null, 'value' => '8']],
            ['4'],
            false,
        ];

        yield 'plain list, every value' => [
            ['8'],
            [['key' => null, 'value' => '8']],
            [],
            true,
        ];

        // The UserPermissions shape: values live under "roles" and "user_groups". Pruning keeps both
        // keys, because acceptValue() requires them — but see testCannotPreserveGrants() for the cases
        // the command refuses to write back.
        yield 'map of lists, one value under one key' => [
            ['roles' => [], 'user_groups' => [11, 13]],
            [['key' => 'user_groups', 'value' => 13]],
            ['roles' => [], 'user_groups' => [11]],
            false,
        ];

        yield 'map of lists, last remaining value' => [
            ['roles' => [], 'user_groups' => [13]],
            [['key' => 'user_groups', 'value' => 13]],
            ['roles' => [], 'user_groups' => []],
            true,
        ];

        yield 'map of lists, values under both keys' => [
            ['roles' => [14, 15], 'user_groups' => [11]],
            [
                ['key' => 'roles', 'value' => 15],
                ['key' => 'user_groups', 'value' => 11],
            ],
            ['roles' => [14], 'user_groups' => []],
            false,
        ];

        yield 'map of lists with a null value list' => [
            ['roles' => null, 'user_groups' => [11]],
            [['key' => 'user_groups', 'value' => 11]],
            ['roles' => null, 'user_groups' => []],
            true,
        ];
    }

    /**
     * @param array<mixed> $limitationValues
     * @param array<array{key: string|null, value: mixed}> $danglingValues
     *
     * @dataProvider providerForTestCannotPreserveGrants
     */
    public function testCannotPreserveGrants(array $limitationValues, array $danglingValues, bool $expected): void
    {
        self::assertSame($expected, $this->buildFinding($limitationValues, $danglingValues)->cannotPreserveGrants());
    }

    /**
     * @return iterable<string, array{array<mixed>, array<array{key: string|null, value: mixed}>, bool}>
     */
    public function providerForTestCannotPreserveGrants(): iterable
    {
        yield 'plain list has no keyed value lists' => [
            ['4', '8'],
            [['key' => null, 'value' => '8']],
            false,
        ];

        yield 'plain list emptied entirely' => [
            ['8'],
            [['key' => null, 'value' => '8']],
            false,
        ];

        // Emptying "roles" while "user_groups" keeps a value would make the Policy grant every Role
        yield 'one value list emptied, another keeps values' => [
            ['roles' => [15], 'user_groups' => [11]],
            [['key' => 'roles', 'value' => 15]],
            true,
        ];

        yield 'value list narrowed but not emptied' => [
            ['roles' => [14, 15], 'user_groups' => [11]],
            [['key' => 'roles', 'value' => 15]],
            false,
        ];

        // Nothing is left anywhere, so the whole Policy goes and nothing is silently widened
        yield 'every value list emptied' => [
            ['roles' => [15], 'user_groups' => [11]],
            [
                ['key' => 'roles', 'value' => 15],
                ['key' => 'user_groups', 'value' => 11],
            ],
            false,
        ];

        yield 'value list that was already empty, another keeps a value' => [
            ['roles' => [], 'user_groups' => [11, 13]],
            [['key' => 'user_groups', 'value' => 13]],
            false,
        ];

        // Pruning "roles" grants every Role; removing the Policy takes away the "user_groups" grant
        yield 'one value list dangling, another already empty' => [
            ['roles' => [15], 'user_groups' => []],
            [['key' => 'roles', 'value' => 15]],
            true,
        ];

        // The mirror: an empty "roles" already grants every Role, so removal would take that away
        yield 'already empty value list, the other fully dangling' => [
            ['roles' => [], 'user_groups' => [11]],
            [['key' => 'user_groups', 'value' => 11]],
            true,
        ];

        // Nothing is granted on either dimension today, so removing the Policy is neutral
        yield 'null value list, the other fully dangling' => [
            ['roles' => null, 'user_groups' => [11]],
            [['key' => 'user_groups', 'value' => 11]],
            false,
        ];
    }

    public function testGetLabels(): void
    {
        $finding = $this->buildFinding(
            ['roles' => [14], 'user_groups' => [11]],
            [
                ['key' => null, 'value' => 858],
                ['key' => 'user_groups', 'value' => 11],
            ]
        );

        self::assertSame(['858', 'user_groups: 11'], $finding->getLabels());
    }

    /**
     * @param array<mixed> $limitationValues
     *
     * @dataProvider providerForTestHoldsNoValues
     */
    public function testHoldsNoValues(array $limitationValues, bool $expected): void
    {
        self::assertSame($expected, DanglingLimitationValues::holdsNoValues($limitationValues));
    }

    /**
     * @return iterable<string, array{array<mixed>, bool}>
     */
    public function providerForTestHoldsNoValues(): iterable
    {
        yield 'empty' => [[], true];
        yield 'plain list with a value' => [['4'], false];
        yield 'map of empty lists' => [['roles' => [], 'user_groups' => []], true];
        yield 'map with a value' => [['roles' => [], 'user_groups' => [11]], false];
        yield 'map of nulls' => [['roles' => null, 'user_groups' => null], true];
    }

    /**
     * @param array<mixed> $limitationValues
     * @param array<array{key: string|null, value: mixed}> $danglingValues
     */
    private function buildFinding(array $limitationValues, array $danglingValues): DanglingLimitationValues
    {
        return new DanglingLimitationValues(
            new Role(['id' => 99, 'identifier' => 'probe_role']),
            new Policy(['id' => 859, 'module' => 'content', 'function' => 'read']),
            $this->buildLimitation($limitationValues),
            $danglingValues
        );
    }

    /**
     * @param array<mixed> $limitationValues
     */
    private function buildLimitation(array $limitationValues): Limitation
    {
        return new ContentTypeLimitation(['limitationValues' => $limitationValues]);
    }
}
