<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Component\Serializer\SerializerTrait;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound\LogicalAnd;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound\LogicalOr;
use PHPUnit\Framework\TestCase;

class MatcherSerializationTest extends TestCase
{
    use SerializerTrait;

    /**
     * @dataProvider matcherProvider
     */
    public function testDeserialize(Matcher $matcher, ?Matcher $expected = null): void
    {
        $serializedMatcher = $this->serializeMatcher($matcher);

        $context = [];
        $deserializeMatcher = $this->deserializeMatcher($serializedMatcher, get_class($matcher), $context);
        $expected = $expected ?? $matcher;

        self::assertEquals($expected, $deserializeMatcher);
    }

    private function serializeMatcher(Matcher $matcher): string
    {
        return $this->getSerializer()->serialize(
            $matcher,
            'json'
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deserializeMatcher(string $serializedMatcher, string $matcherFQCN, array $context): Matcher
    {
        return $this->getSerializer()->deserialize(
            $serializedMatcher,
            $matcherFQCN,
            'json',
            $context
        );
    }

    /**
     * @return iterable<string, array{0: \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher, 1?: \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher}>
     */
    public function matcherProvider(): iterable
    {
        $subMatchers = [
            Matcher\Map\URI::class => new Matcher\Map\URI(['campaign' => 'event']),
            Matcher\Map\Host::class => new Matcher\Map\Host(['www.example.org' => 'site']),
            Matcher\HostElement::class => new Matcher\HostElement(1),
        ];
        // data truncated due to https://issues.ibexa.co/browse/EZP-31810
        $expectedSubMatchers = [
            Matcher\Map\URI::class => new Matcher\Map\URI([]),
            Matcher\Map\Host::class => new Matcher\Map\Host([]),
            Matcher\HostElement::class => new Matcher\HostElement(1),
        ];
        $compoundMatcherConfig = [
            [
                'matchers' => [
                    Matcher\Map\URI::class => ['match' => 'site_access_name'],
                    Matcher\Map\Host::class => ['match' => 'site_access_name'],
                    Matcher\HostElement::class => ['match' => 'site_access_name'],
                ],
                'match' => 'site_access_name',
            ],
        ];
        $logicalAnd = new LogicalAnd($compoundMatcherConfig);
        $logicalAnd->setSubMatchers($subMatchers);
        $expectedLogicalAnd = new LogicalAnd([]);
        $expectedLogicalAnd->setSubMatchers($expectedSubMatchers);

        $logicalOr = new LogicalOr($compoundMatcherConfig);
        $logicalOr->setSubMatchers($subMatchers);
        $expectedLogicalOr = new LogicalOr([]);
        $expectedLogicalOr->setSubMatchers($expectedSubMatchers);

        $expectedMapURI = new Matcher\Map\URI([]);
        $expectedMapURI->setMapKey('site');

        yield 'URIText' => [
            new Matcher\URIText(
                [
                    'prefix' => 'foo',
                    'suffix' => 'bar',
                ]
            ),
        ];
        yield 'HostText' => [
            new Matcher\HostText(
                [
                    'prefix' => 'foo',
                    'suffix' => 'bar',
                ]
            ),
        ];
        yield 'URIElement' => [
                new Matcher\URIElement(
                    [
                    'elementNumber' => 2,
                ]
                ),
        ];
        yield 'HostElement' => [
            new Matcher\HostElement(
                [
                    'elementNumber' => 2,
                ]
            ),
        ];
        yield 'MapURI' => $this->getMapURIMatcherTestCase();
        yield 'MapPort' => $this->getMapPortMatcherTestCase();
        yield 'MapHost' => $this->getMapHostMatcherTestCase();
        yield 'CompoundAnd' => [
            $logicalAnd,
            $expectedLogicalAnd,
        ];
        yield 'CompoundOr' => [
            $logicalOr,
            $expectedLogicalOr,
        ];
    }

    /**
     * @return array{\Ibexa\Core\MVC\Symfony\SiteAccess\Matcher, \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher}
     */
    private function getMapPortMatcherTestCase(): array
    {
        $matcherBeforeSerialization = new Matcher\Map\Port(['8080' => 'event']);
        $matcherBeforeSerialization->setMapKey('8080');

        $matcherAfterDeserialization = new Matcher\Map\Port([]);
        $matcherAfterDeserialization->setMapKey('8080');

        return [$matcherBeforeSerialization, $matcherAfterDeserialization];
    }

    /**
     * @return array{\Ibexa\Core\MVC\Symfony\SiteAccess\Matcher, \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher}
     */
    private function getMapHostMatcherTestCase(): array
    {
        $matcherBeforeSerialization = new Matcher\Map\Host(['map' => 'site']);
        $matcherBeforeSerialization->setMapKey('map');

        $matcherAfterDeserialization = new Matcher\Map\Host([]);
        $matcherAfterDeserialization->setMapKey('map');

        return [$matcherBeforeSerialization, $matcherAfterDeserialization];
    }

    /**
     * @return array{\Ibexa\Core\MVC\Symfony\SiteAccess\Matcher, \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher}
     */
    private function getMapURIMatcherTestCase(): array
    {
        $matcherBeforeSerialization = new Matcher\Map\URI(['www.example.org' => 'event_site']);
        $matcherBeforeSerialization->setMapKey('www.example.org');

        $matcherAfterDeserialization = new Matcher\Map\URI([]);
        $matcherAfterDeserialization->setMapKey('www.example.org');

        return [$matcherBeforeSerialization, $matcherAfterDeserialization];
    }
}
