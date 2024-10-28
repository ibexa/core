<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Fragment;

use Ibexa\Bundle\Core\Fragment\InlineFragmentRenderer;
use Ibexa\Bundle\Core\Fragment\SiteAccessSerializer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\SerializerTrait;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @covers \Ibexa\Bundle\Core\Fragment\InlineFragmentRenderer
 */
class InlineFragmentRendererTest extends DecoratedFragmentRendererTest
{
    use SerializerTrait;

    public function testRendererControllerReference()
    {
        $reference = new ControllerReference('FooBundle:bar:baz');
        $matcher = new SiteAccess\Matcher\HostElement(1);
        $siteAccess = new SiteAccess(
            'test',
            'test',
            $matcher
        );
        $request = new Request();
        $request->attributes->set('siteaccess', $siteAccess);
        $request->attributes->set('semanticPathinfo', '/foo/bar');
        $request->attributes->set('viewParametersString', '/(foo)/bar');
        $options = ['foo' => 'bar'];
        $expectedReturn = '/_fragment?foo=bar';
        $this->innerRenderer
            ->expects(self::once())
            ->method('render')
            ->with($reference, $request, $options)
            ->will(self::returnValue($expectedReturn));

        $renderer = $this->getRenderer();
        self::assertSame($expectedReturn, $renderer->render($reference, $request, $options));
        self::assertTrue(isset($reference->attributes['serialized_siteaccess']));
        $serializedSiteAccess = json_encode($siteAccess);
        self::assertSame($serializedSiteAccess, $reference->attributes['serialized_siteaccess']);
        self::assertTrue(isset($reference->attributes['serialized_siteaccess_matcher']));
        self::assertSame(
            $this->getSerializer()->serialize(
                $siteAccess->matcher,
                'json',
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
            ),
            $reference->attributes['serialized_siteaccess_matcher']
        );
        self::assertTrue(isset($reference->attributes['semanticPathinfo']));
        self::assertSame('/foo/bar', $reference->attributes['semanticPathinfo']);
        self::assertTrue(isset($reference->attributes['viewParametersString']));
        self::assertSame('/(foo)/bar', $reference->attributes['viewParametersString']);
    }

    public function testRendererControllerReferenceWithCompoundMatcher(): ControllerReference
    {
        $reference = parent::testRendererControllerReferenceWithCompoundMatcher();

        self::assertArrayHasKey('semanticPathinfo', $reference->attributes);
        self::assertSame('/foo/bar', $reference->attributes['semanticPathinfo']);
        self::assertArrayHasKey('viewParametersString', $reference->attributes);
        self::assertSame('/(foo)/bar', $reference->attributes['viewParametersString']);

        return $reference;
    }

    public function getRequest(SiteAccess $siteAccess): Request
    {
        $request = new Request();
        $request->attributes->set('siteaccess', $siteAccess);
        $request->attributes->set('semanticPathinfo', '/foo/bar');
        $request->attributes->set('viewParametersString', '/(foo)/bar');

        return $request;
    }

    public function getRenderer(): FragmentRendererInterface
    {
        return new InlineFragmentRenderer($this->innerRenderer, new SiteAccessSerializer($this->getSerializer()));
    }
}
