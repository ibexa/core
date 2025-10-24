<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Section;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Section as SectionIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SectionTest extends BaseTestCase
{
    /** @var Section */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new SectionIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
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
                $this->generateLocationForSectionId(123),
                true,
            ],
            [
                123,
                $this->generateLocationForSectionId(456),
                false,
            ],
            [
                [123, 789],
                $this->generateLocationForSectionId(456),
                false,
            ],
            [
                [123, 789],
                $this->generateLocationForSectionId(789),
                true,
            ],
        ];
    }

    /**
     * Generates a Location mock in respect of a given content Id.
     *
     * @param int $sectionId
     *
     * @return MockObject
     */
    private function generateLocationForSectionId($sectionId)
    {
        $location = $this->getLocationMock();
        $location
            ->expects(self::any())
            ->method('getContentInfo')
            ->will(
                self::returnValue(
                    $this->getContentInfoMock(['sectionId' => $sectionId])
                )
            );

        return $location;
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Section::matchContentInfo
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
                $this->getContentInfoMock(['sectionId' => 123]),
                true,
            ],
            [
                123,
                $this->getContentInfoMock(['sectionId' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getContentInfoMock(['sectionId' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getContentInfoMock(['sectionId' => 789]),
                true,
            ],
        ];
    }
}
