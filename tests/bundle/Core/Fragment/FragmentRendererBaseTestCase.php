<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Fragment;

use Ibexa\Core\MVC\Symfony\Component\Serializer\SerializerTrait;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class FragmentRendererBaseTestCase extends TestCase
{
    use SerializerTrait;

    protected FragmentRendererInterface & MockObject $innerRenderer;

    public function testRendererControllerReferenceWithCompoundMatcher(): ControllerReference
    {
        $reference = new ControllerReference('FooBundle:bar:baz');
        $compoundMatcher = new SiteAccess\Matcher\Compound\LogicalAnd([]);
        $subMatchers = [
            'Map\URI' => new SiteAccess\Matcher\Map\URI([]),
            'Map\Host' => new SiteAccess\Matcher\Map\Host([]),
        ];
        $compoundMatcher->setSubMatchers($subMatchers);
        $siteAccess = new SiteAccess(
            'test',
            'test',
            $compoundMatcher
        );

        $request = $this->getRequest($siteAccess);
        $options = ['foo' => 'bar'];
        $expectedReturn = new Response('/_fragment?foo=bar');
        $this->innerRenderer
            ->expects(self::once())
            ->method('render')
            ->with($reference, $request, $options)
            ->willReturn($expectedReturn)
        ;

        $renderer = $this->getRenderer();
        self::assertSame($expectedReturn, $renderer->render($reference, $request, $options));
        self::assertArrayHasKey('serialized_siteaccess', $reference->attributes);
        $serializedSiteAccess = json_encode($siteAccess);
        self::assertSame($serializedSiteAccess, $reference->attributes['serialized_siteaccess']);
        self::assertArrayHasKey('serialized_siteaccess_matcher', $reference->attributes);
        self::assertSame(
            $this->getSerializer()->serialize(
                $siteAccess->matcher,
                'json',
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
            ),
            $reference->attributes['serialized_siteaccess_matcher']
        );
        self::assertArrayHasKey('serialized_siteaccess_sub_matchers', $reference->attributes);
        if ($siteAccess->matcher instanceof SiteAccess\Matcher\CompoundInterface) {
            $this->assertSubMatchers($reference, $siteAccess->matcher);
        }

        return $reference;
    }

    abstract public function getRequest(SiteAccess $siteAccess): Request;

    abstract public function getRenderer(): FragmentRendererInterface;

    private function assertSubMatchers(
        ControllerReference $reference,
        SiteAccess\Matcher\CompoundInterface $compoundMatcher
    ): void {
        foreach ($compoundMatcher->getSubMatchers() as $subMatcher) {
            self::assertSame(
                $this->getSerializer()->serialize(
                    $subMatcher,
                    'json',
                    [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
                ),
                $reference->attributes['serialized_siteaccess_sub_matchers'][get_class($subMatcher)]
            );
        }
    }
}
