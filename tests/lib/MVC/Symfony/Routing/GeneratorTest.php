<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Core\MVC\Symfony\Routing\Generator;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class GeneratorTest extends TestCase
{
    /** @var Generator|MockObject */
    private $generator;

    /** @var MockObject */
    private $siteAccessRouter;

    /** @var MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteAccessRouter = $this->createMock(SiteAccessRouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->generator = $this->getMockForAbstractClass(Generator::class);
        $this->generator->setSiteAccessRouter($this->siteAccessRouter);
        $this->generator->setLogger($this->logger);
    }

    public function generateProvider()
    {
        return [
            ['foo_bar', [], UrlGeneratorInterface::ABSOLUTE_PATH],
            ['foo_bar', [], UrlGeneratorInterface::ABSOLUTE_URL],
            ['foo_bar', ['some' => 'thing'], UrlGeneratorInterface::ABSOLUTE_URL],
            [new Location(), [], UrlGeneratorInterface::ABSOLUTE_PATH],
            [new Location(), [], UrlGeneratorInterface::ABSOLUTE_URL],
            [new Location(), ['some' => 'thing'], UrlGeneratorInterface::ABSOLUTE_URL],
            [new \stdClass(), [], UrlGeneratorInterface::ABSOLUTE_PATH],
            [new \stdClass(), [], UrlGeneratorInterface::ABSOLUTE_URL],
            [new \stdClass(), ['some' => 'thing'], UrlGeneratorInterface::ABSOLUTE_URL],
        ];
    }

    /**
     * @dataProvider generateProvider
     */
    public function testSimpleGenerate(
        $urlResource,
        array $parameters,
        $referenceType
    ) {
        $matcher = $this->createMock(URILexer::class);
        $this->generator->setSiteAccess(new SiteAccess('test', 'fake', $matcher));

        $baseUrl = '/base/url';
        $requestContext = new RequestContext($baseUrl);
        $this->generator->setRequestContext($requestContext);

        $uri = '/some/thing';
        $this->generator
            ->expects(self::once())
            ->method('doGenerate')
            ->with($urlResource, $parameters)
            ->will(self::returnValue($uri));

        $fullUri = $baseUrl . $uri;
        $matcher
            ->expects(self::once())
            ->method('analyseLink')
            ->with($uri)
            ->will(self::returnValue($uri));

        if ($referenceType === UrlGeneratorInterface::ABSOLUTE_URL) {
            $fullUri = $requestContext->getScheme() . '://' . $requestContext->getHost() . $baseUrl . $uri;
        }

        self::assertSame($fullUri, $this->generator->generate($urlResource, $parameters, $referenceType));
    }

    /**
     * @dataProvider generateProvider
     */
    public function testGenerateWithSiteAccessNoReverseMatch(
        $urlResource,
        array $parameters,
        $referenceType
    ) {
        $matcher = $this->createMock(URILexer::class);
        $this->generator->setSiteAccess(new SiteAccess('test', 'test', $matcher));

        $baseUrl = '/base/url';
        $requestContext = new RequestContext($baseUrl);
        $this->generator->setRequestContext($requestContext);

        $uri = '/some/thing';
        $this->generator
            ->expects(self::once())
            ->method('doGenerate')
            ->with($urlResource, $parameters)
            ->will(self::returnValue($uri));

        $fullUri = $baseUrl . $uri;
        $matcher
            ->expects(self::once())
            ->method('analyseLink')
            ->with($uri)
            ->will(self::returnValue($uri));

        if ($referenceType === UrlGeneratorInterface::ABSOLUTE_URL) {
            $fullUri = $requestContext->getScheme() . '://' . $requestContext->getHost() . $baseUrl . $uri;
        }

        $siteAccessName = 'fake';
        $this->siteAccessRouter
            ->expects(self::once())
            ->method('matchByName')
            ->with($siteAccessName)
            ->will(self::returnValue(null));
        $this->logger
            ->expects(self::once())
            ->method('notice');
        self::assertSame($fullUri, $this->generator->generate($urlResource, $parameters + ['siteaccess' => $siteAccessName], $referenceType));
    }
}
