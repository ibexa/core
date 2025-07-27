<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Languages;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\Yaml\Yaml;

class LanguagesTest extends AbstractParserTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new IbexaCoreExtension([new Languages()])];
    }

    protected function getMinimalConfiguration(): array
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_minimal.yml'));
    }

    public function testLanguagesSingleSiteaccess()
    {
        $langDemoSite = ['eng-GB'];
        $langFre = ['fre-FR', 'eng-GB'];
        $langEmptyGroup = ['pol-PL'];
        $config = [
            'siteaccess' => [
                'list' => ['fre2'],
                'groups' => [self::EMPTY_SA_GROUP => []],
            ],
            'system' => [
                'ibexa_demo_site' => ['languages' => $langDemoSite],
                'fre' => ['languages' => $langFre],
                'fre2' => ['languages' => $langFre],
                self::EMPTY_SA_GROUP => ['languages' => $langEmptyGroup],
            ],
        ];
        $this->load($config);

        $this->assertConfigResolverParameterValue('languages', $langDemoSite, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('languages', $langFre, 'fre');
        $this->assertConfigResolverParameterValue('languages', $langFre, 'fre2');
        $this->assertConfigResolverParameterValue('languages', $langEmptyGroup, self::EMPTY_SA_GROUP);
        self::assertSame(
            [
                'eng-GB' => ['ibexa_demo_site'],
                'fre-FR' => ['fre', 'fre2'],
                'pol-PL' => [self::EMPTY_SA_GROUP],
            ],
            $this->container->getParameter('ibexa.site_access.by_language')
        );
        // languages for ibexa_demo_site_admin will take default value (empty array)
        $this->assertConfigResolverParameterValue('languages', [], 'ibexa_demo_site_admin');
    }

    public function testLanguagesSiteaccessGroup()
    {
        $langDemoSite = ['eng-US', 'eng-GB'];
        $config = [
            'system' => [
                'ibexa_demo_frontend_group' => ['languages' => $langDemoSite],
                'ibexa_demo_site' => [],
                'fre' => [],
            ],
        ];
        $this->load($config);

        $this->assertConfigResolverParameterValue('languages', $langDemoSite, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('languages', $langDemoSite, 'fre');
        $this->assertConfigResolverParameterValue('languages', [], self::EMPTY_SA_GROUP);
        self::assertSame(
            [
                'eng-US' => ['ibexa_demo_frontend_group', 'ibexa_demo_site', 'fre'],
            ],
            $this->container->getParameter('ibexa.site_access.by_language')
        );
        // languages for ibexa_demo_site_admin will take default value (empty array)
        $this->assertConfigResolverParameterValue('languages', [], 'ibexa_demo_site_admin');
    }

    public function testTranslationSiteAccesses()
    {
        $translationSAsDemoSite = ['foo', 'bar'];
        $translationSAsFre = ['foo2', 'bar2'];
        $config = [
            'system' => [
                'ibexa_demo_site' => ['translation_siteaccesses' => $translationSAsDemoSite],
                'fre' => ['translation_siteaccesses' => $translationSAsFre],
            ],
        ];
        $this->load($config);

        $this->assertConfigResolverParameterValue('translation_siteaccesses', $translationSAsDemoSite, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('translation_siteaccesses', $translationSAsFre, 'fre');
        $this->assertConfigResolverParameterValue('translation_siteaccesses', [], 'ibexa_demo_site_admin');
        $this->assertConfigResolverParameterValue('translation_siteaccesses', [], self::EMPTY_SA_GROUP);
    }

    public function testTranslationSiteAccessesWithGroup()
    {
        $translationSAsDemoSite = ['ibexa_demo_site', 'fre'];
        $config = [
            'system' => [
                'ibexa_demo_frontend_group' => ['translation_siteaccesses' => $translationSAsDemoSite],
                'ibexa_demo_site' => [],
                'fre' => [],
            ],
        ];
        $this->load($config);

        $this->assertConfigResolverParameterValue('translation_siteaccesses', $translationSAsDemoSite, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue('translation_siteaccesses', $translationSAsDemoSite, 'fre');
        $this->assertConfigResolverParameterValue('translation_siteaccesses', [], 'ibexa_demo_site_admin');
        $this->assertConfigResolverParameterValue('translation_siteaccesses', [], self::EMPTY_SA_GROUP);
    }
}
