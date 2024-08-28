<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\CompoundMatcherNormalizer;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound;
use Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs\CompoundStub;
use Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs\MatcherStub;
use Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs\SerializerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class CompoundMatcherNormalizerTest extends TestCase
{
    public function testNormalization(): void
    {
        $matcher = new CompoundStub([]);
        $matcher->setSubMatchers(
            [
                'foo' => new MatcherStub('foo'),
                'bar' => new MatcherStub('bar'),
                'baz' => new MatcherStub('baz'),
            ]
        );

        $normalizer = new CompoundMatcherNormalizer();
        $serializer = new Serializer(
            [
                $normalizer,
                new SerializerStub(),
                new ObjectNormalizer(),
            ]
        );

        self::assertEquals(
            [
                'subMatchers' => [
                    'foo' => ['data' => 'foo'],
                    'bar' => ['data' => 'bar'],
                    'baz' => ['data' => 'baz'],
                ],
                'config' => [],
                'matchersMap' => [],
            ],
            $serializer->normalize($matcher)
        );
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new CompoundMatcherNormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(Compound::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }

    /**
     * @throws \JsonException
     */
    public function testSupportsDenormalization(): void
    {
        $normalizer = new CompoundMatcherNormalizer();

        $data = json_encode(
            [
                'subMatchers' => [
                    'foo' => ['data' => 'foo'],
                ],
                'config' => [],
                'matchersMap' => [],
            ],
            JSON_THROW_ON_ERROR
        );

        self::assertTrue($normalizer->supportsDenormalization($data, Compound::class, 'json'));
        self::assertFalse($normalizer->supportsDenormalization($data, Matcher::class, 'json'));
    }
}
