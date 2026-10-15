<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\ContentView;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\Yaml\Yaml;

class ViewTest extends AbstractParserTestCase
{
    private $config;

    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension([new ContentView()]),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return $this->config = Yaml::parse(file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_view.yml'));
    }

    public function testContentView(): void
    {
        $this->load();
        $expectedContentView = $this->config['system']['ibexa_demo_frontend_group']['content_view'];
        foreach ($expectedContentView as &$rulesets) {
            foreach ($rulesets as &$config) {
                if (!isset($config['params'])) {
                    $config['params'] = [];
                }
            }
        }

        $this->assertConfigResolverParameterValue('content_view', $expectedContentView, 'ibexa_demo_site', false);
        $this->assertConfigResolverParameterValue('content_view', $expectedContentView, 'fre', false);
        $this->assertConfigResolverParameterValue('content_view', [], 'ibexa_demo_site_admin', false);
    }
}
