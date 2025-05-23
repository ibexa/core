<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\MapNormalizer;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map as MapMatcher;
use PHPUnit\Framework\TestCase;

final class MapNormalizerTest extends TestCase
{
    public function testNormalization(): void
    {
        $normalizer = new MapNormalizer();

        $matcher = $this->createMock(MapMatcher::class);
        $matcher->method('getMapKey')->willReturn('foo');

        self::assertEquals(
            [
                'key' => 'foo',
                'map' => [],
                'reverseMap' => [],
                'type' => $matcher::class,
            ],
            $normalizer->normalize($matcher)
        );
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new MapNormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(MapMatcher::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }
}
