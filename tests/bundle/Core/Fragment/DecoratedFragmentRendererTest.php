<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Fragment;

use Ibexa\Bundle\Core\Fragment\DecoratedFragmentRenderer;
use Ibexa\Bundle\Core\Fragment\SiteAccessSerializer;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\HttpKernel\Fragment\RoutableFragmentRenderer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @covers \Ibexa\Bundle\Core\Fragment\DecoratedFragmentRenderer
 */
class DecoratedFragmentRendererTest extends FragmentRendererBaseTestCase
{
    protected FragmentRendererInterface & MockObject $innerRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerRenderer = $this->createMock(FragmentRendererInterface::class);
    }

    public function testSetFragmentPathNotRoutableRenderer(): void
    {
        $matcher = $this->createMock(SiteAccess\URILexer::class);
        $siteAccess = new SiteAccess('test', 'test', $matcher);
        $matcher
            ->expects(self::never())
            ->method('analyseLink');

        $renderer = $this->getRenderer();
        $renderer->setSiteAccess($siteAccess);
        if ($renderer instanceof RoutableFragmentRenderer) {
            $renderer->setFragmentPath('foo');
        }
    }

    public function testSetFragmentPath(): void
    {
        $matcher = $this->createMock(SiteAccess\URILexer::class);
        $siteAccess = new SiteAccess('test', 'test', $matcher);
        $matcher
            ->expects(self::once())
            ->method('analyseLink')
            ->with('/foo')
            ->willReturn('/bar/foo');

        $innerRenderer = $this->createMock(RoutableFragmentRenderer::class);
        $innerRenderer
            ->expects(self::once())
            ->method('setFragmentPath')
            ->with('/bar/foo');
        $renderer = new DecoratedFragmentRenderer($innerRenderer, new SiteAccessSerializer($this->getSerializer()));
        $renderer->setSiteAccess($siteAccess);
        $renderer->setFragmentPath('/foo');
    }

    public function testGetName(): void
    {
        $name = 'test';
        $this->innerRenderer
            ->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        $renderer = $this->getRenderer();
        self::assertSame($name, $renderer->getName());
    }

    public function testRendererAbsoluteUrl(): void
    {
        $url = 'http://phoenix-rises.fm/foo/bar';
        $request = new Request();
        $options = ['foo' => 'bar'];
        $expectedReturn = new Response('/_fragment?foo=bar');
        $this->innerRenderer
            ->expects(self::once())
            ->method('render')
            ->with($url, $request, $options)
            ->willReturn($expectedReturn);

        $renderer = $this->getRenderer();
        self::assertEquals($expectedReturn, $renderer->render($url, $request, $options));
    }

    public function testRendererControllerReference(): void
    {
        $reference = new ControllerReference('FooBundle:bar:baz');
        $matcher = new SiteAccess\Matcher\URIElement(1);
        $siteAccess = new SiteAccess(
            'test',
            'test',
            $matcher
        );
        $request = new Request();
        $request->attributes->set('siteaccess', $siteAccess);
        $options = ['foo' => 'bar'];
        $expectedReturn = new Response('/_fragment?foo=bar');
        $this->innerRenderer
            ->expects(self::once())
            ->method('render')
            ->with($reference, $request, $options)
            ->willReturn($expectedReturn);

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
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request']]
            ),
            $reference->attributes['serialized_siteaccess_matcher']
        );
    }

    public function getRequest(SiteAccess $siteAccess): Request
    {
        $request = new Request();
        $request->attributes->set('siteaccess', $siteAccess);

        return $request;
    }

    /**
     * @return FragmentRendererInterface&SiteAccessAware
     */
    public function getRenderer(): FragmentRendererInterface
    {
        return new DecoratedFragmentRenderer($this->innerRenderer, new SiteAccessSerializer($this->getSerializer()));
    }
}
