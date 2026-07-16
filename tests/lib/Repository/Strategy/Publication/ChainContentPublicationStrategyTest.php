<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Strategy\Publication;

use Ibexa\Contracts\Core\Repository\Strategy\ContentPublication\ContentPublicationStrategyInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Strategy\ContentPublication\ChainContentPublicationStrategy;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ChainContentPublicationStrategyTest extends TestCase
{
    public function testPublishVersionExecutesFirstSupportingStrategy(): void
    {
        $versionInfo = $this->createStub(VersionInfo::class);

        $notSupportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $notSupportingStrategy
            ->expects(self::once())
            ->method('supports')
            ->willReturn(false);
        $notSupportingStrategy
            ->expects(self::never())
            ->method('publishVersion');

        $supportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $supportingStrategy
            ->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $supportingStrategy
            ->expects(self::once())
            ->method('publishVersion')
            ->with(self::identicalTo($versionInfo), ['eng-GB']);

        $neverConsultedStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $neverConsultedStrategy
            ->expects(self::never())
            ->method('supports');
        $neverConsultedStrategy
            ->expects(self::never())
            ->method('publishVersion');

        $chain = new ChainContentPublicationStrategy([
            $notSupportingStrategy,
            $supportingStrategy,
            $neverConsultedStrategy,
        ]);

        $chain->publishVersion($versionInfo, ['eng-GB']);
    }

    public function testSupportsReturnsTrueWhenAnyStrategySupports(): void
    {
        $notSupportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $notSupportingStrategy
            ->expects(self::once())
            ->method('supports')
            ->willReturn(false);

        $supportingStrategy = $this->createMock(ContentPublicationStrategyInterface::class);
        $supportingStrategy
            ->expects(self::once())
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
            ->expects(self::once())
            ->method('supports')
            ->willReturn(false);
        $notSupportingStrategy
            ->expects(self::never())
            ->method('publishVersion');

        $chain = new ChainContentPublicationStrategy([$notSupportingStrategy]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No content publication strategy supports the current publication.');

        $chain->publishVersion($this->createMock(VersionInfo::class));
    }
}
