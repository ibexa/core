<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote as RemoteIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;

class RemoteTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote */
    private Remote $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new RemoteIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param string|string[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param bool $expectedResult
     */
    public function testMatchLocation(string|array $matchingConfig, Location $location, bool $expectedResult): void
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($location));
    }

    public function matchLocationProvider(): array
    {
        return [
            [
                'foo',
                $this->getLocationMock(['remoteId' => 'foo']),
                true,
            ],
            [
                'foo',
                $this->getLocationMock(['remoteId' => 'bar']),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->getLocationMock(['remoteId' => 'bar']),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->getLocationMock(['remoteId' => 'baz']),
                true,
            ],
        ];
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param string|string[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param bool $expectedResult
     */
    public function testMatchContentInfo(string|array $matchingConfig, ContentInfo $contentInfo, bool $expectedResult): void
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchContentInfo($contentInfo));
    }

    public function matchContentInfoProvider(): array
    {
        return [
            [
                'foo',
                $this->getContentInfoMock(['remoteId' => 'foo']),
                true,
            ],
            [
                'foo',
                $this->getContentInfoMock(['remoteId' => 'bar']),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->getContentInfoMock(['remoteId' => 'bar']),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->getContentInfoMock(['remoteId' => 'baz']),
                true,
            ],
        ];
    }
}
