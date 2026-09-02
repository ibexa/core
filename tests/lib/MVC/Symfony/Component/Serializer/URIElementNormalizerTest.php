<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\URIElementNormalizer;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Component\Serializer\URIElementNormalizer
 */
final class URIElementNormalizerTest extends TestCase
{
    /**
     * @dataProvider provideForTestNormalization
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function testNormalization(bool $initializeUriElements): void
    {
        $normalizer = new URIElementNormalizer();
        $serializer = new Serializer(
            [
                $normalizer,
                new ObjectNormalizer(),
            ]
        );

        $matcher = new URIElement(2);
        $matcher->setRequest(SimplifiedRequest::fromUrl('https://ibexa.dev/foo/bar'));
        if ($initializeUriElements) {
            $matcher->match();
        }

        self::assertEquals(
            [
                'type' => URIElement::class,
                'elementNumber' => 2,
                'uriElements' => ['foo', 'bar'],
            ],
            $serializer->normalize($matcher)
        );
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideForTestNormalization(): iterable
    {
        yield 'uriElements initialized by match()' => [true];
        // uriElements must be computed from the request during normalization (IBX-12102)
        yield 'uriElements not yet initialized' => [false];
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new URIElementNormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(URIElement::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }
}
