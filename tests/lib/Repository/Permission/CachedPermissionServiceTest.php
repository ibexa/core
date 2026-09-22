<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Permission;

/**
 * Avoid test failure caused by time passing between generating expected & actual object.
 *
 * @return int
 */
function time()
{
    static $time = 1417624981;

    return ++$time;
}

namespace Ibexa\Tests\Core\Repository\Permission;

use Ibexa\Contracts\Core\Repository\PermissionCriterionResolver;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Repository\Permission\CachedPermissionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mock test case for CachedPermissionService.
 */
class CachedPermissionServiceTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    public static function providerForTestPermissionResolverPassTrough(): array
    {
        $valueObject = self::createStub(ValueObject::class);

        $userRef = self::createStub(UserReference::class);

        $repository = self::createStub(Repository::class);

        return [
            ['getCurrentUserReference', [], $userRef],
            ['setCurrentUserReference', [$userRef], null],
            ['hasAccess', ['content', 'remove', $userRef], false],
            ['canUser', ['content', 'remove', $valueObject, [new \stdClass()]], true],
            ['sudo', [static function (): void {}, $repository], null],
        ];
    }

    /**
     * Test for all PermissionResolver methods when they just pass true to underlying service.
     *
     *
     * @param $method
     * @param array $arguments
     * @param $expectedReturn
     */
    #[DataProvider('providerForTestPermissionResolverPassTrough')]
    public function testPermissionResolverPassTrough($method, array $arguments, $expectedReturn): void
    {
        if ($expectedReturn !== null) {
            $this->getPermissionResolverMock([$method])
                ->expects(self::once())
                ->method($method)
                ->with(...$arguments)
                ->willReturn($expectedReturn);
        } else {
            $this->getPermissionResolverMock([$method])
                ->expects(self::once())
                ->method($method)
                ->with(...$arguments);
        }

        $cachedService = $this->getCachedPermissionService();

        $actualReturn = $cachedService->$method(...$arguments);
        self::assertSame($expectedReturn, $actualReturn);
    }

    public function testGetPermissionsCriterionPassTrough(): void
    {
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getPermissionCriterionResolverMock(['getPermissionsCriterion'])
            ->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'remove')
            ->willReturn($criterionMock);

        $cachedService = $this->getCachedPermissionService();

        $actualReturn = $cachedService->getPermissionsCriterion('content', 'remove');
        self::assertSame($criterionMock, $actualReturn);
    }

    public function testGetPermissionsCriterionCaching(): void
    {
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getPermissionCriterionResolverMock(['getPermissionsCriterion'])
            ->expects(self::exactly(2))
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->willReturn($criterionMock);

        $cachedService = $this->getCachedPermissionService(2);

        $actualReturn = $cachedService->getPermissionsCriterion('content', 'read');
        self::assertSame($criterionMock, $actualReturn);

        // +1
        $actualReturn = $cachedService->getPermissionsCriterion('content', 'read');
        self::assertSame($criterionMock, $actualReturn);

        // +3, time() will be called twice and cache will be updated
        $actualReturn = $cachedService->getPermissionsCriterion('content', 'read');
        self::assertSame($criterionMock, $actualReturn);
    }

    public function testSetCurrentUserReferenceCacheClear(): void
    {
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getPermissionCriterionResolverMock(['getPermissionsCriterion'])
            ->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->willReturn($criterionMock);

        $cachedService = $this->getCachedPermissionService(2);

        $actualReturn = $cachedService->getPermissionsCriterion('content', 'read');
        self::assertSame($criterionMock, $actualReturn);

        $userRef = $this
            ->getMockBuilder(UserReference::class)
            ->getMockForAbstractClass();
        $cachedService->setCurrentUserReference($userRef);
    }

    /**
     * Returns the CachedPermissionService to test against.
     *
     * @param int $ttl
     *
     * @return \Ibexa\Core\Repository\Permission\CachedPermissionService
     */
    protected function getCachedPermissionService($ttl = 5)
    {
        return new CachedPermissionService(
            $this->getPermissionResolverMock(),
            $this->getPermissionCriterionResolverMock(),
            $ttl
        );
    }

    protected $permissionResolverMock;

    protected function getPermissionResolverMock($methods = [])
    {
        // Tests first calls here with methods set before initiating PermissionCriterionResolver with same instance.
        if ($this->permissionResolverMock !== null) {
            return $this->permissionResolverMock;
        }

        return $this->permissionResolverMock = $this
            ->getMockBuilder(PermissionResolver::class)
            ->disableOriginalConstructor()
            // sudo() is not part of the PermissionResolver interface but is called on the
            // concrete implementation at runtime; addMethods() lets it be stubbed here too.
            ->addMethods(['sudo'])
            ->getMockForAbstractClass();
    }

    protected $permissionCriterionResolverMock;

    protected function getPermissionCriterionResolverMock($methods = [])
    {
        // Tests first calls here with methods set before initiating PermissionCriterionResolver with same instance.
        if ($this->permissionCriterionResolverMock !== null) {
            return $this->permissionCriterionResolverMock;
        }

        return $this->permissionCriterionResolverMock = $this
            ->getMockBuilder(PermissionCriterionResolver::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
}
