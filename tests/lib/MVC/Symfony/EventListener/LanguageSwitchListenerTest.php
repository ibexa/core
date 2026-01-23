<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\EventListener;

use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\MVC\Symfony\Event\RouteReferenceGenerationEvent;
use Ibexa\Core\MVC\Symfony\EventListener\LanguageSwitchListener;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\RouteReference;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LanguageSwitchListenerTest extends TestCase
{
    /** @var MockObject */
    private $translationHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translationHelper = $this->getMockBuilder(TranslationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [MVCEvents::ROUTE_REFERENCE_GENERATION => 'onRouteReferenceGeneration'],
            LanguageSwitchListener::getSubscribedEvents()
        );
    }

    public function testOnRouteReferenceGenerationNoLanguage()
    {
        $this->translationHelper
            ->expects(self::never())
            ->method('getTranslationSiteAccess');

        $event = new RouteReferenceGenerationEvent(new RouteReference('foo'), new Request());
        $listener = new LanguageSwitchListener($this->translationHelper);
        $listener->onRouteReferenceGeneration($event);
    }

    public function testOnRouteReferenceGeneration()
    {
        $language = 'fre-FR';
        $routeReference = new RouteReference('foo', ['language' => $language]);
        $event = new RouteReferenceGenerationEvent($routeReference, new Request());
        $expectedSiteAccess = 'phoenix_rises';
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslationSiteAccess')
            ->with($language)
            ->will(self::returnValue($expectedSiteAccess));

        $listener = new LanguageSwitchListener($this->translationHelper);
        $listener->onRouteReferenceGeneration($event);
        self::assertFalse($routeReference->has('language'));
        self::assertTrue($routeReference->has('siteaccess'));
        self::assertSame($expectedSiteAccess, $routeReference->get('siteaccess'));
    }

    public function testOnRouteReferenceGenerationNoTranslationSiteAccess()
    {
        $language = 'fre-FR';
        $routeReference = new RouteReference('foo', ['language' => $language]);
        $event = new RouteReferenceGenerationEvent($routeReference, new Request());
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslationSiteAccess')
            ->with($language)
            ->will(self::returnValue(null));

        $listener = new LanguageSwitchListener($this->translationHelper);
        $listener->onRouteReferenceGeneration($event);
        self::assertFalse($routeReference->has('language'));
        self::assertFalse($routeReference->has('siteaccess'));
    }
}
