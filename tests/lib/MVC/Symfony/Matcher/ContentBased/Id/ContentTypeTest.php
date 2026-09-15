<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType as ContentTypeIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;

#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType::class, 'matchLocation')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::class, 'setMatchingConfig')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType::class, 'matchContentInfo')]
class ContentTypeTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentType */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentTypeIdMatcher();
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('matchLocationProvider')]
    public function testMatchLocation($matchingConfig, int $contentTypeId, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($this->generateLocationForContentType($contentTypeId)));
    }

    public static function matchLocationProvider()
    {
        $data = [];

        $data[] = [
            123,
            123,
            true,
        ];

        $data[] = [
            123,
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            789,
            true,
        ];

        return $data;
    }

    /**
     * Generates a Location object in respect of a given content type identifier.
     *
     * @param int $contentTypeId
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function generateContentInfoForContentType($contentTypeId)
    {
        return $this->getContentInfoMock(['contentTypeId' => $contentTypeId]);
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('matchContentInfoProvider')]
    public function testMatchContentInfo($matchingConfig, int $contentTypeId, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchContentInfo($this->generateContentInfoForContentType($contentTypeId)));
    }

    public static function matchContentInfoProvider()
    {
        $data = [];

        $data[] = [
            123,
            123,
            true,
        ];

        $data[] = [
            123,
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            789,
            true,
        ];

        return $data;
    }
}
