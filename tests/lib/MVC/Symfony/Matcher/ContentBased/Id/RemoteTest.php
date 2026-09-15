<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote as RemoteIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;

#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote::class, 'matchLocation')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::class, 'setMatchingConfig')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote::class, 'matchContentInfo')]
class RemoteTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\Remote */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new RemoteIdMatcher();
    }

    /**
     * @param string|string[] $matchingConfig
     * @param bool $expectedResult
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('matchLocationProvider')]
    public function testMatchLocation($matchingConfig, string $remoteId, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($this->getLocationMock(['remoteId' => $remoteId])));
    }

    public static function matchLocationProvider()
    {
        return [
            [
                'foo',
                'foo',
                true,
            ],
            [
                'foo',
                'bar',
                false,
            ],
            [
                ['foo', 'baz'],
                'bar',
                false,
            ],
            [
                ['foo', 'baz'],
                'baz',
                true,
            ],
        ];
    }

    /**
     * @param string|string[] $matchingConfig
     * @param bool $expectedResult
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('matchContentInfoProvider')]
    public function testMatchContentInfo($matchingConfig, string $remoteId, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchContentInfo($this->getContentInfoMock(['remoteId' => $remoteId])));
    }

    public static function matchContentInfoProvider()
    {
        return [
            [
                'foo',
                'foo',
                true,
            ],
            [
                'foo',
                'bar',
                false,
            ],
            [
                ['foo', 'baz'],
                'bar',
                false,
            ],
            [
                ['foo', 'baz'],
                'baz',
                true,
            ],
        ];
    }
}
