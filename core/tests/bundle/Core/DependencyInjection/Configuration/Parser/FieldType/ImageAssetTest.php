<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser\FieldType;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\FieldType\ImageAsset as ImageAssetConfigParser;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser\AbstractParserTestCase;

class ImageAssetTest extends AbstractParserTestCase
{
    /**
     * @{@inheritdoc}
     */
    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension([new ImageAssetConfigParser()]),
        ];
    }

    public function testDefaultImageAssetSettings()
    {
        $this->load();

        $this->assertConfigResolverParameterValue(
            'fieldtypes.ibexa_image_asset.mappings',
            [
                'content_type_identifier' => 'image',
                'content_field_identifier' => 'image',
                'name_field_identifier' => 'name',
                'parent_location_id' => 51,
            ],
            'ibexa_demo_site'
        );
    }

    /**
     * @dataProvider imageAssetSettingsProvider
     */
    public function testImageAssetSettings(array $config, array $expected)
    {
        $this->load(
            [
                'system' => [
                    'ibexa_demo_site' => $config,
                ],
            ]
        );

        foreach ($expected as $key => $val) {
            $this->assertConfigResolverParameterValue($key, $val, 'ibexa_demo_site');
        }
    }

    public function imageAssetSettingsProvider(): array
    {
        return [
            [
                [
                    'fieldtypes' => [
                        'ibexa_image_asset' => [
                            'content_type_identifier' => 'photo',
                            'content_field_identifier' => 'file',
                            'name_field_identifier' => 'title',
                            'parent_location_id' => 68,
                        ],
                    ],
                ],
                [
                    'fieldtypes.ibexa_image_asset.mappings' => [
                        'content_type_identifier' => 'photo',
                        'content_field_identifier' => 'file',
                        'name_field_identifier' => 'title',
                        'parent_location_id' => 68,
                    ],
                ],
            ],
        ];
    }
}
