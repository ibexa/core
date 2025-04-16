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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Component\Serializer\CompoundMatcherNormalizer
 *
 * @phpstan-type TNormalizedData array{type?: class-string, subMatchers: array<mixed>, config: array<mixed>, matchersMap: array<mixed>}
 */
final class CompoundMatcherNormalizerTest extends TestCase
{
    /** @phpstan-var TNormalizedData */
    private const array DATA = [
        'type' => CompoundStub::class,
        'subMatchers' => [
            'foo' => ['type' => MatcherStub::class, 'data' => 'foo'],
            'bar' => ['type' => MatcherStub::class, 'data' => 'bar'],
            'baz' => ['type' => MatcherStub::class, 'data' => 'baz'],
        ],
        'config' => [],
        'matchersMap' => [],
    ];

    /**
     * @phpstan-return TNormalizedData
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function testNormalization(): array
    {
        $matcher = new CompoundStub([]);
        $subMatchers = $this->getSubMatchers();
        $matcher->setSubMatchers(
            $subMatchers
        );

        $normalizer = new CompoundMatcherNormalizer();
        $innerNormalizerMock = $this->createMock(NormalizerInterface::class);
        $innerNormalizerMock
            ->expects(self::once())
            ->method('normalize')
            ->with($subMatchers, null, [])
            ->willReturn(
                [
                    'foo' => ['type' => MatcherStub::class, 'data' => 'foo'],
                    'bar' => ['type' => MatcherStub::class, 'data' => 'bar'],
                    'baz' => ['type' => MatcherStub::class, 'data' => 'baz'],
                ]
            );

        $normalizer->setNormalizer($innerNormalizerMock);

        $actualNormalizedData = $normalizer->normalize($matcher);
        self::assertEquals(
            self::DATA,
            $actualNormalizedData
        );

        /** @phpstan-var TNormalizedData  */
        return $actualNormalizedData;
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new CompoundMatcherNormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(Compound::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }

    /**
     * @phpstan-return iterable<array{TNormalizedData, class-string, bool}>
     */
    public static function getDataForSupportsNormalization(): iterable
    {
        yield [self::DATA, Compound::class, true];
        yield [self::DATA, Matcher::class, false];

        $dataWithMissingType = self::DATA;
        unset($dataWithMissingType['type']);

        yield [$dataWithMissingType, Compound::class, false];
    }

    /**
     * @dataProvider getDataForSupportsNormalization
     *
     * @phpstan-param TNormalizedData $data
     * @phpstan-param class-string $type
     */
    public function testSupportsDenormalization(array $data, string $type, bool $supports): void
    {
        $normalizer = new CompoundMatcherNormalizer();

        self::assertSame($supports, $normalizer->supportsDenormalization($data, $type));
    }

    /**
     * @depends testNormalization
     *
     * @phpstan-param array{type: class-string, subMatchers: array<mixed>, config: array{}, matchersMap: array{}} $data
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function testDenormalization(array $data): void
    {
        $expectedCompoundMatcher = new CompoundStub($this->getSubMatchers());
        $normalizer = new CompoundMatcherNormalizer();
        $innerDenormalizerMock = $this->createMock(DenormalizerInterface::class);
        $innerDenormalizerMock
            ->expects(self::exactly(count($data['subMatchers'])))
            ->method('denormalize')
            ->willReturnCallback(
                static fn (array $data): Matcher => new MatcherStub($data['data'] ?? [])
            );
        $normalizer->setDenormalizer($innerDenormalizerMock);
        $actualCompoundMatcher = $normalizer->denormalize($data, Compound::class);

        self::assertEquals($expectedCompoundMatcher, $actualCompoundMatcher);
    }

    /**
     * @return array<string, \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher>
     */
    private function getSubMatchers(): array
    {
        return [
            'foo' => new MatcherStub('foo'),
            'bar' => new MatcherStub('bar'),
            'baz' => new MatcherStub('baz'),
        ];
    }
}
