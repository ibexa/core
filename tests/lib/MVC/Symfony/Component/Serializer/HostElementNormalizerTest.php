<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Component\Serializer\HostElementNormalizer;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\HostElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Component\Serializer\HostElementNormalizer
 */
final class HostElementNormalizerTest extends TestCase
{
    private const array DATA = [
        'type' => HostElement::class,
        'elementNumber' => 2,
        'hostElements' => [
            'ibexa',
            'dev',
        ],
    ];

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function testNormalization(): void
    {
        $normalizer = new HostElementNormalizer();
        $serializer = new Serializer(
            [
                $normalizer,
                new ObjectNormalizer(),
            ]
        );

        $matcher = new HostElement(2);
        // Set request and invoke match to initialize HostElement::$hostElements
        $matcher->setRequest(SimplifiedRequest::fromUrl('https://ibexa.dev/foo/bar'));
        $matcher->match();

        self::assertEquals(
            self::DATA,
            $serializer->normalize($matcher)
        );
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new HostElementNormalizer();

        self::assertTrue($normalizer->supportsNormalization($this->createMock(HostElement::class)));
        self::assertFalse($normalizer->supportsNormalization($this->createMock(Matcher::class)));
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function testDenormalization(): void
    {
        $denormalizer = new HostElementNormalizer();
        $expectedHostElement = new HostElement(2);
        $expectedHostElement->setRequest(SimplifiedRequest::fromUrl('https://ibexa.dev/foo/bar'));
        $actualHostElement = $denormalizer->denormalize(self::DATA, HostElement::class);

        self::assertSame('dev', $actualHostElement->match());
    }
}
