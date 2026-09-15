<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Section as SectionIdMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversMethod(MultipleValued::class, 'setMatchingConfig')]
#[CoversMethod(SectionIdMatcher::class, 'matchContentInfo')]
class SectionTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Section */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new SectionIdMatcher();
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchLocation($matchingConfig, int $sectionId, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($this->generateLocationForSectionId($sectionId)));
    }

    public static function matchLocationProvider()
    {
        return [
            [
                123,
                123,
                true,
            ],
            [
                123,
                456,
                false,
            ],
            [
                [123, 789],
                456,
                false,
            ],
            [
                [123, 789],
                789,
                true,
            ],
        ];
    }

    /**
     * Generates a Location mock in respect of a given content Id.
     *
     * @param int $sectionId
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchContentInfoProvider')]
    public function testMatchContentInfo($matchingConfig, int $sectionId, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchContentInfo($this->getContentInfoMock(['sectionId' => $sectionId])));
    }

    public static function matchContentInfoProvider()
    {
        return [
            [
                123,
                123,
                true,
            ],
            [
                123,
                456,
                false,
            ],
            [
                [123, 789],
                456,
                false,
            ],
            [
                [123, 789],
                789,
                true,
            ],
        ];
    }
}
