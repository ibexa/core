<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType as ContentTypeIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ContentTypeTest extends BaseTestCase
{
    /** @var ContentType */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentTypeIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType::matchLocation
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
        $data = [];

        $data[] = [
            123,
            $this->generateLocationForContentType(123),
            true,
        ];

        $data[] = [
            123,
            $this->generateLocationForContentType(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateLocationForContentType(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateLocationForContentType(789),
            true,
        ];

        return $data;
    }

    /**
     * Generates a Location object in respect of a given content type identifier.
     *
     * @param int $contentTypeId
     *
     * @return MockObject
     */
    private function generateLocationForContentType($contentTypeId)
    {
        $location = $this->getLocationMock();
        $location
            ->expects(self::any())
            ->method('getContentInfo')
            ->will(
                self::returnValue(
                    $this->generateContentInfoForContentType($contentTypeId)
                )
            );

        return $location;
    }

    /**
     * Generates a ContentInfo object in respect of a given content type identifier.
     *
     * @param int $contentTypeId
     *
     * @return MockObject
     */
    private function generateContentInfoForContentType($contentTypeId)
    {
        return $this->getContentInfoMock(['contentTypeId' => $contentTypeId]);
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType::matchContentInfo
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
        $data = [];

        $data[] = [
            123,
            $this->generateContentInfoForContentType(123),
            true,
        ];

        $data[] = [
            123,
            $this->generateContentInfoForContentType(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateContentInfoForContentType(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateContentInfoForContentType(789),
            true,
        ];

        return $data;
    }
}
