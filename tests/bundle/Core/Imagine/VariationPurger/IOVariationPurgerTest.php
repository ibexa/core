<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Bundle\Core\Imagine\Cache\AliasGeneratorDecorator;
use Ibexa\Bundle\Core\Imagine\VariationPurger\IOVariationPurger;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class IOVariationPurgerTest extends TestCase
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
        $ioService
            ->expects(self::exactly(2))
            ->method('deleteDirectory')
            ->withConsecutive(
                ['_aliases/medium'],
                ['_aliases/large']
            );
        $cacheIdentifierGenerator
            ->expects(self::exactly(2))
            ->method('generateTag')
            ->withConsecutive(
                ['image_variation_name', ['medium']],
                ['image_variation_name', ['large']]
            )
            ->willReturnOnConsecutiveCalls('ign-medium', 'ign-large');
        $tagAwareAdapter
            ->expects(self::exactly(2))
            ->method('invalidateTags')
            ->withConsecutive(
                [['ign-medium']],
                [['ign-large']]
            );

        $purger = new IOVariationPurger(
            $ioService,
            $tagAwareAdapter,
            $cacheIdentifierGenerator,
            $aliasGeneratorDecorator
        );

        $purger->purge(['medium', 'large']);
    }
}

class_alias(IOVariationPurgerTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\Imagine\VariationPurger\IOVariationPurgerTest');
