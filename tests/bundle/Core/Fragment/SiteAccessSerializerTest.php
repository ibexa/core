<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Fragment;

use Ibexa\Bundle\Core\Fragment\SiteAccessSerializer;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs\CompoundStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\Serializer\SerializerInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Bundle\Core\Fragment\SiteAccessSerializer::class)]
final class SiteAccessSerializerTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDataForTestSerializeSiteAccessAsControllerAttributes')]
    public function testSerializeSiteAccessAsControllerAttributes(SiteAccess $siteAccess): void
    {
        $serializerMock = $this->createMock(SerializerInterface::class);
        $siteAccessSerializer = new SiteAccessSerializer($serializerMock);

        $controllerReference = new ControllerReference('foo');

        $serializerMock->method('serialize')
                       ->with(
                           self::isInstanceOf(SiteAccess\Matcher::class),
                           'json',
                           self::isArray()
                       )->willReturn('{"foo":"bar"}')
        ;

        $siteAccessSerializer->serializeSiteAccessAsControllerAttributes($siteAccess, $controllerReference);

        self::assertJson($controllerReference->attributes['serialized_siteaccess']);

        // this just tests internal flow instead of actual serializer, covered elsewhere. Hence, comparing to mocked values
        self::assertSame('{"foo":"bar"}', $controllerReference->attributes['serialized_siteaccess_matcher']);
        if ($siteAccess->matcher instanceof SiteAccess\Matcher\CompoundInterface) {
            foreach ($siteAccess->matcher->getSubMatchers() as $subMatcher) {
                self::assertJson(
                    $controllerReference->attributes['serialized_siteaccess_sub_matchers'][get_class($subMatcher)]
                );
            }
        }
    }

    /**
     * @return iterable<string, array{\Ibexa\Core\MVC\Symfony\SiteAccess}>
     */
    public static function getDataForTestSerializeSiteAccessAsControllerAttributes(): iterable
    {
        yield 'SiteAccess with simple matcher' => [
            new SiteAccess('foo', SiteAccess::DEFAULT_MATCHING_TYPE, new SiteAccess\Matcher\URIElement(1)),
        ];

        yield 'SiteAccess with compound matcher' => [
            new SiteAccess(
                'foo',
                SiteAccess::DEFAULT_MATCHING_TYPE,
                new CompoundStub(
                    [
                        new SiteAccess\Matcher\HostElement(2),
                        new SiteAccess\Matcher\URIElement(2),
                    ]
                )
            ),
        ];
    }
}
