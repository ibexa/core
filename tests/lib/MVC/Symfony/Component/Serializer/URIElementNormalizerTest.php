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
use Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs\SerializerStub;
use PHPUnit\Framework\TestCase;

final class URIElementNormalizerTest extends TestCase
{
    /**
     * @dataProvider provideForTestNormalization
     */
    public function testNormalization(bool $initializeUriElements): void
    {
        $normalizer = new URIElementNormalizer();
        $normalizer->setSerializer(new SerializerStub());

        $matcher = new URIElement(2);
        $matcher->setRequest(SimplifiedRequest::fromUrl('https://ezpublish.dev/foo/bar'));
        if ($initializeUriElements) {
            $matcher->match();
        }

        $this->assertEquals(
            [
                'elementNumber' => 2,
                'uriElements' => ['foo', 'bar'],
            ],
            $normalizer->normalize($matcher)
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

        $this->assertTrue($normalizer->supportsNormalization($this->createMock(URIElement::class)));
        $this->assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }
}

class_alias(URIElementNormalizerTest::class, 'eZ\Publish\Core\MVC\Symfony\Component\Tests\Serializer\URIElementNormalizerTest');
