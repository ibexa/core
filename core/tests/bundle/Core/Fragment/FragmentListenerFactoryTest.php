<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Fragment;

use Ibexa\Bundle\Core\Fragment\FragmentListenerFactory;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\EventListener\FragmentListener;

class FragmentListenerFactoryTest extends TestCase
{
    /**
     * @dataProvider buildFragmentListenerProvider
     */
    public function testBuildFragmentListener($requestUri, $isFragmentCandidate)
    {
        $listenerClass = FragmentListener::class;
        $uriSigner = new UriSigner('my_precious_secret');
        $baseFragmentPath = '/_fragment';
        $request = Request::create($requestUri);
        $requestStack = new RequestStack([$request]);

        $factory = new FragmentListenerFactory();
        $factory->setRequestStack($requestStack);
        $listener = $factory->buildFragmentListener($uriSigner, $baseFragmentPath, $listenerClass);
        self::assertInstanceOf($listenerClass, $listener);

        $refListener = new ReflectionObject($listener);
        $refFragmentPath = $refListener->getProperty('fragmentPath');
        $refFragmentPath->setAccessible(true);
        if ($isFragmentCandidate) {
            self::assertSame($requestUri, $refFragmentPath->getValue($listener));
        } else {
            self::assertSame($baseFragmentPath, $refFragmentPath->getValue($listener));
        }
    }

    public function buildFragmentListenerProvider()
    {
        return [
            ['/foo/bar', false],
            ['/foo', false],
            ['/_fragment', true],
            ['/my_siteaccess/_fragment', true],
            ['/foo/_fragment/something', false],
            ['/_fragment/something', false],
        ];
    }

    public function testBuildFragmentListenerNoRequest()
    {
        $factory = new FragmentListenerFactory();
        $factory->setRequestStack(new RequestStack());

        $listener = $factory->buildFragmentListener(
            new UriSigner('my_precious_secret'),
            '/_fragment',
            FragmentListener::class
        );

        self::assertNull($listener);
    }
}
