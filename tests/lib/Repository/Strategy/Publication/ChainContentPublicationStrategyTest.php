<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Strategy\Publication;

use Ibexa\Contracts\Core\Repository\Strategy\ContentPublication\ContentPublicationResult;
use Ibexa\Contracts\Core\Repository\Strategy\ContentPublication\ContentPublicationStrategyInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Strategy\ContentPublication\ChainContentPublicationStrategy;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ChainContentPublicationStrategyTest extends TestCase
{
    public function testPublishVersionExecutesFirstSupportingStrategy(): void
    {
        $versionInfo = self::createStub(VersionInfo::class);
        $result = new ContentPublicationResult(null);

        $notSupportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $notSupportingStrategy
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $notSupportingStrategy
            ->expects($this->never())
            ->method('publishVersion');

        $supportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $supportingStrategy
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $supportingStrategy
            ->expects($this->once())
            ->method('publishVersion')
            ->with(self::identicalTo($versionInfo), ['eng-GB'])
            ->willReturn($result);

        $neverConsultedStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $neverConsultedStrategy
            ->expects($this->never())
            ->method('supports');
        $neverConsultedStrategy
            ->expects($this->never())
            ->method('publishVersion');

        $chain = new ChainContentPublicationStrategy([
            $notSupportingStrategy,
            $supportingStrategy,
            $neverConsultedStrategy,
        ]);

        self::assertSame($result, $chain->publishVersion($versionInfo, ['eng-GB']));
    }

    public function testSupportsReturnsTrueWhenAnyStrategySupports(): void
    {
        $notSupportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $notSupportingStrategy
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $supportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $supportingStrategy
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $chain = new ChainContentPublicationStrategy([$notSupportingStrategy, $supportingStrategy]);

        self::assertTrue($chain->supports());
    }

    public function testSupportsReturnsFalseForEmptyChain(): void
    {
        $chain = new ChainContentPublicationStrategy([]);

        self::assertFalse($chain->supports());
    }

    public function testPublishVersionThrowsLogicExceptionWhenNoStrategySupports(): void
    {
        $notSupportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $notSupportingStrategy
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $notSupportingStrategy
            ->expects($this->never())
            ->method('publishVersion');

        $chain = new ChainContentPublicationStrategy([$notSupportingStrategy]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No content publication strategy supports the current publication.');

        $chain->publishVersion(self::createStub(VersionInfo::class));
    }
}
