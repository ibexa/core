<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Content;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Content as ContentIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ContentTest extends BaseTestCase
{
    /** @var Content */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Content::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param Location $location
     * @param bool $expectedResult
     */
    public function testMatchLocation(
        $matchingConfig,
        Location $location,
        $expectedResult
    ) {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($location));
    }

    public function matchLocationProvider()
    {
        return [
            [
                123,
                $this->generateLocationForContentId(123),
                true,
            ],
            [
                123,
                $this->generateLocationForContentId(456),
                false,
            ],
            [
                [123, 789],
                $this->generateLocationForContentId(456),
                false,
            ],
            [
                [123, 789],
                $this->generateLocationForContentId(789),
                true,
            ],
        ];
    }

    /**
     * Generates a Location mock in respect of a given content Id.
     *
     * @param int $contentId
     *
     * @return MockObject
     */
    private function generateLocationForContentId($contentId)
    {
        $location = $this->getLocationMock();
        $location
            ->expects(self::any())
            ->method('getContentInfo')
            ->will(
                self::returnValue(
                    $this->getContentInfoMock(['id' => $contentId])
                )
            );

        return $location;
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Content::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param ContentInfo $contentInfo
     * @param bool $expectedResult
     */
    public function testMatchContentInfo(
        $matchingConfig,
        ContentInfo $contentInfo,
        $expectedResult
    ) {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchContentInfo($contentInfo));
    }

    public function matchContentInfoProvider()
    {
        return [
            [
                123,
                $this->getContentInfoMock(['id' => 123]),
                true,
            ],
            [
                123,
                $this->getContentInfoMock(['id' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getContentInfoMock(['id' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getContentInfoMock(['id' => 789]),
                true,
            ],
        ];
    }
}
