<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\URITextNormalizer;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIText;
use Ibexa\Tests\Core\Search\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Component\Serializer\URITextNormalizer
 */
final class URITextNormalizerTest extends TestCase
{
    /**
     * @throws ExceptionInterface
     */
    public function testNormalize(): void
    {
        $normalizer = new URITextNormalizer();
        $serializer = new Serializer(
            [
                $normalizer,
                new ObjectNormalizer(),
            ]
        );

        $matcher = new URIText([
            'prefix' => 'foo',
            'suffix' => 'bar',
        ]);

        self::assertEquals(
            [
                'type' => URIText::class,
                'siteAccessesConfiguration' => [
                    'prefix' => 'foo',
                    'suffix' => 'bar',
                ],
            ],
            $serializer->normalize($matcher)
        );
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new URITextNormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(URIText::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }
}
