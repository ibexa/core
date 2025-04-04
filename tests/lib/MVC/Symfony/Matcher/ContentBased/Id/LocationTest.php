<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Location as LocationIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTest;

class LocationTest extends BaseTest
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Location */
    private LocationIdMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new LocationIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Location::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param bool $expectedResult
     */
    public function testMatchLocation(int|array $matchingConfig, Location $location, bool $expectedResult): void
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($location));
    }

    public function matchLocationProvider(): array
    {
        return [
            [
                123,
                $this->getLocationMock(['id' => 123]),
                true,
            ],
            [
                123,
                $this->getLocationMock(['id' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getLocationMock(['id' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getLocationMock(['id' => 789]),
                true,
            ],
        ];
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Location::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param bool $expectedResult
     */
    public function testMatchContentInfo(int|array $matchingConfig, ContentInfo $contentInfo, bool $expectedResult): void
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchContentInfo($contentInfo));
    }

    public function matchContentInfoProvider(): array
    {
        return [
            [
                123,
                $this->getContentInfoMock(['mainLocationId' => 123]),
                true,
            ],
            [
                123,
                $this->getContentInfoMock(['mainLocationId' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getContentInfoMock(['mainLocationId' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getContentInfoMock(['mainLocationId' => 789]),
                true,
            ],
        ];
    }
}
