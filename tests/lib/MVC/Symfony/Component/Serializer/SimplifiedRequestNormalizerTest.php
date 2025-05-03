<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\SimplifiedRequestNormalizer;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Component\Serializer\SimplifiedRequestNormalizer
 */
final class SimplifiedRequestNormalizerTest extends TestCase
{
    /**
     * @todo Remove together with old syntax for \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest::__construct in 6.0.0
     */
    public function testNormalize(): void
    {
        $request = new SimplifiedRequest([
            'scheme' => 'http',
            'host' => 'www.example.com',
            'port' => 8080,
            'pathinfo' => '/foo',
            'queryParams' => ['param' => 'value', 'this' => 'that'],
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0',
                'Cookie' => 'IBX_SESSION_ID21232f297a57a5a743894a0e4a801fc3=mgbs2p6lv936hb5hmdd2cvq6bq',
                'Connection' => 'keep-alive',
            ],
            'languages' => ['pl-PL', 'en-US'],
        ]);

        $normalizer = new SimplifiedRequestNormalizer();

        self::assertEquals([
            'scheme' => 'http',
            'host' => 'www.example.com',
            'port' => 8080,
            'pathInfo' => '/foo',
            'queryParams' => ['param' => 'value', 'this' => 'that'],
            'headers' => [],
            'languages' => ['pl-PL', 'en-US'],
        ], $normalizer->normalize($request));
    }

    public function testNormalizeWithNewConstructor(): void
    {
        $request = new SimplifiedRequest(
            'http',
            'www.example.com',
            8080,
            '/foo',
            ['param' => 'value', 'this' => 'that'],
            ['pl-PL', 'en-US'],
            [
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0',
                'Cookie' => 'eZSESSID21232f297a57a5a743894a0e4a801fc3=mgbs2p6lv936hb5hmdd2cvq6bq',
                'Connection' => 'keep-alive',
            ],
        );

        $normalizer = new SimplifiedRequestNormalizer();

        self::assertEquals([
            'scheme' => 'http',
            'host' => 'www.example.com',
            'port' => 8080,
            'pathInfo' => '/foo',
            'queryParams' => ['param' => 'value', 'this' => 'that'],
            'headers' => [],
            'languages' => ['pl-PL', 'en-US'],
        ], $normalizer->normalize($request));
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new SimplifiedRequestNormalizer();

        self::assertTrue($normalizer->supportsNormalization(new SimplifiedRequest()));
        self::assertFalse($normalizer->supportsNormalization(new stdClass()));
    }
}
