<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Matcher;

use Ibexa\Bundle\Core\Matcher\ViewMatcherRegistry;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Matcher\ViewMatcherRegistry
 */
final class ViewMatcherRegistryTest extends TestCase
{
    private const MATCHER_NAME = 'test_matcher';

    /**
     * @throws NotFoundException
     */
    public function testGetMatcher(): void
    {
        $matcher = $this->getMatcherMock();
        $registry = new ViewMatcherRegistry([self::MATCHER_NAME => $matcher]);

        self::assertSame($matcher, $registry->getMatcher(self::MATCHER_NAME));
    }

    /**
     * @throws NotFoundException
     */
    public function testSetMatcher(): void
    {
        $matcher = $this->getMatcherMock();
        $registry = new ViewMatcherRegistry();

        $registry->setMatcher(self::MATCHER_NAME, $matcher);

        self::assertSame($matcher, $registry->getMatcher(self::MATCHER_NAME));
    }

    /**
     * @throws NotFoundException
     */
    public function testSetMatcherOverride(): void
    {
        $matcher = $this->getMatcherMock();
        $newMatcher = $this->getMatcherMock();
        $registry = new ViewMatcherRegistry([self::MATCHER_NAME => $matcher]);

        $registry->setMatcher(self::MATCHER_NAME, $newMatcher);

        self::assertSame($newMatcher, $registry->getMatcher(self::MATCHER_NAME));
    }

    public function testGetMatcherNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $registry = new ViewMatcherRegistry();

        $registry->getMatcher(self::MATCHER_NAME);
    }

    protected function getMatcherMock(): ViewMatcherInterface
    {
        return $this->createMock(ViewMatcherInterface::class);
    }
}
