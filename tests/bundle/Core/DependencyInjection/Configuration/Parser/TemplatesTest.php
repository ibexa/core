<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\FieldDefinitionSettingsTemplates;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\FieldTemplates;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Symfony\Component\Yaml\Yaml;

class TemplatesTest extends AbstractParserTestCase
{
    private $config;

    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension(
                [new FieldTemplates(), new FieldDefinitionSettingsTemplates()]
            ),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return $this->config = Yaml::parse(file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_templates.yml'));
    }

    public function testFieldTemplates()
    {
        $this->load();
        $fixedUpConfig = $this->getExpectedConfigFieldTemplates($this->config);
        $groupFieldTemplates = $fixedUpConfig['system']['ibexa_demo_frontend_group']['field_templates'];
        $demoSiteFieldTemplates = $fixedUpConfig['system']['ibexa_demo_site']['field_templates'];
        $this->assertConfigResolverParameterValue(
            'field_templates',
            array_merge(
                // Adding default kernel value.
                [['template' => '%ibexa.default_templates.field_templates%', 'priority' => 0]],
                $groupFieldTemplates,
                $demoSiteFieldTemplates
            ),
            'ibexa_demo_site',
            false
        );
        $this->assertConfigResolverParameterValue(
            'field_templates',
            array_merge(
                // Adding default kernel value.
                [['template' => '%ibexa.default_templates.field_templates%', 'priority' => 0]],
                $groupFieldTemplates
            ),
            'fre',
            false
        );
        $this->assertConfigResolverParameterValue(
            'field_templates',
            [['template' => '%ibexa.default_templates.field_templates%', 'priority' => 0]],
            'ibexa_demo_site_admin',
            false
        );
    }

    protected function getSiteAccessProviderMock(): SiteAccessProviderInterface
    {
        $siteAccessProvider = $this->createMock(SiteAccessProviderInterface::class);
        $siteAccessProvider
            ->method('isDefined')
            ->willReturnMap([
                ['ibexa_demo_site', true],
                ['fre', true],
                ['ibexa_demo_site_admin', true],
            ]);
        $siteAccessProvider
            ->method('getSiteAccess')
            ->willReturnMap([
                ['ibexa_demo_site', $this->getSiteAccess('ibexa_demo_site', StaticSiteAccessProvider::class, ['ibexa_demo_group', 'ibexa_demo_frontend_group'])],
                ['fre', $this->getSiteAccess('fre', StaticSiteAccessProvider::class, ['ibexa_demo_group', 'ibexa_demo_frontend_group'])],
                ['ibexa_demo_site_admin', $this->getSiteAccess('ibexa_demo_site_admin', StaticSiteAccessProvider::class, ['ibexa_demo_group'])],
            ]);

        return $siteAccessProvider;
    }

    /**
     * Fixes up input configuration for field_templates as semantic configuration parser does, adding a default priority of 0 when not set.
     *
     * @param array $config
     *
     * @return array
     */
    private function getExpectedConfigFieldTemplates(array $config)
    {
        foreach ($config['system']['ibexa_demo_frontend_group']['field_templates'] as &$block) {
            if (!isset($block['priority'])) {
                $block['priority'] = 0;
            }
        }

        return $config;
    }

    public function testFieldDefinitionSettingsTemplates()
    {
        $this->load();
        $fixedUpConfig = $this->getExpectedConfigFieldDefinitionSettingsTemplates($this->config);
        $groupFieldTemplates = $fixedUpConfig['system']['ibexa_demo_frontend_group']['fielddefinition_settings_templates'];
        $demoSiteFieldTemplates = $fixedUpConfig['system']['ibexa_demo_site']['fielddefinition_settings_templates'];

        $this->assertConfigResolverParameterValue(
            'fielddefinition_settings_templates',
            array_merge(
                // Adding default kernel value.
                [['template' => '%ibexa.default_templates.fielddefinition_settings_templates%', 'priority' => 0]],
                $groupFieldTemplates,
                $demoSiteFieldTemplates
            ),
            'ibexa_demo_site',
            false
        );
        $this->assertConfigResolverParameterValue(
            'fielddefinition_settings_templates',
            array_merge(
                // Adding default kernel value.
                [['template' => '%ibexa.default_templates.fielddefinition_settings_templates%', 'priority' => 0]],
                $groupFieldTemplates
            ),
            'fre',
            false
        );
        $this->assertConfigResolverParameterValue(
            'fielddefinition_settings_templates',
            [['template' => '%ibexa.default_templates.fielddefinition_settings_templates%', 'priority' => 0]],
            'ibexa_demo_site_admin',
            false
        );
    }

    /**
     * Fixes up input configuration for fielddefinition_settings_templates as semantic configuration parser does, adding a default priority of 0 when not set.
     *
     * @param array $config
     *
     * @return array
     */
    private function getExpectedConfigFieldDefinitionSettingsTemplates(array $config)
    {
        foreach ($config['system']['ibexa_demo_frontend_group']['fielddefinition_settings_templates'] as &$block) {
            if (!isset($block['priority'])) {
                $block['priority'] = 0;
            }
        }

        return $config;
    }
}
