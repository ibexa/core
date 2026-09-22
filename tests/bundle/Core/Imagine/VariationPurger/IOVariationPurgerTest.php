<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Bundle\Core\Imagine\Cache\AliasGeneratorDecorator;
use Ibexa\Bundle\Core\Imagine\VariationPurger\IOVariationPurger;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

#[CoversClass(IOVariationPurger::class)]
final class IOVariationPurgerTest extends TestCase
{
    public function testPurgesAliasList(): void
    {
        $ioService = $this->createMock(IOServiceInterface::class);
        $tagAwareAdapter = $this->createMock(TagAwareAdapterInterface::class);
        $cacheIdentifierGenerator = $this->createMock(CacheIdentifierGeneratorInterface::class);
        $aliasGeneratorDecorator = $this->createMock(AliasGeneratorDecorator::class);

        $aliasGeneratorDecorator
            ->expects(self::once())
            ->method('getVariationNameTag')
            ->willReturn('image_variation_name');
        $matcher = self::exactly(2);
        $ioService
            ->expects($matcher)
            ->method('deleteDirectory')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('_aliases/medium', $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('_aliases/large', $parameters[0]);
                }
            });
        $matcher = self::exactly(2);
        $cacheIdentifierGenerator
            ->expects($matcher)
            ->method('generateTag')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('image_variation_name', $parameters[0]);
                    $this->assertSame(['medium'], $parameters[1]);

                    return 'ign-medium';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('image_variation_name', $parameters[0]);
                    $this->assertSame(['large'], $parameters[1]);

                    return 'ign-large';
                }
            });
        $matcher = self::exactly(2);
        $tagAwareAdapter
            ->expects($matcher)
            ->method('invalidateTags')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame(['ign-medium'], $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame(['ign-large'], $parameters[0]);
                }

                return false;
            });

        $purger = new IOVariationPurger(
            $ioService,
            $tagAwareAdapter,
            $cacheIdentifierGenerator,
            $aliasGeneratorDecorator
        );

        $purger->purge(['medium', 'large']);
    }
}
