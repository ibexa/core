<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\RegexURINormalizer;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Regex\URI;
use Ibexa\Tests\Core\Search\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class RegexURINormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $normalizer = new RegexURINormalizer();
        $serializer = new Serializer(
            [
                $normalizer,
                new ObjectNormalizer(),
            ]
        );

        $matcher = new URI([
            'regex' => '/^Foo(.*)/(.*)/',
            'itemNumber' => 2,
        ]);

        self::assertEquals(
            [
                'siteAccessesConfiguration' => [
                    'regex' => '/^Foo(.*)/(.*)/',
                    'itemNumber' => 2,
                ],
            ],
            $serializer->normalize($matcher)
        );
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new RegexURINormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(URI::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }
}
