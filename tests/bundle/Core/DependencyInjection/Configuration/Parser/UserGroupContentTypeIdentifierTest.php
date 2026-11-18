<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\UserGroupContentTypeIdentifier;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(UserGroupContentTypeIdentifier::class)]
final class UserGroupContentTypeIdentifierTest extends AbstractParserTestCase
{
    private const string PARAMETER = 'user_group_content_type_identifier';

    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension([new UserGroupContentTypeIdentifier()]),
        ];
    }

    /**
     * @return array<mixed>
     */
    protected function getMinimalConfiguration(): array
    {
        $input = file_get_contents(__DIR__ . '/../../Fixtures/ezpublish_minimal.yml');

        if ($input === false) {
            self::fail('Failed to load ezpublish_minimal.yml');
        }

        return Yaml::parse($input);
    }

    public function testDefaultsToBuiltInUserGroupContentType(): void
    {
        $this->load();

        $this->assertConfigResolverParameterValue(self::PARAMETER, ['user_group'], 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue(self::PARAMETER, ['user_group'], 'ibexa_demo_site_admin');
    }

    public function testDefaultScopeAppliesToAllSiteAccesses(): void
    {
        $identifiers = ['user_group', 'custom_user_group'];

        $this->load(['system' => ['default' => [self::PARAMETER => $identifiers]]]);

        $this->assertConfigResolverParameterValue(self::PARAMETER, $identifiers, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue(self::PARAMETER, $identifiers, 'ibexa_demo_site_admin');
    }

    public function testGroupScopeAppliesOnlyToItsSiteAccesses(): void
    {
        $identifiers = ['user_group', 'custom_user_group'];

        $this->load(['system' => ['ibexa_demo_frontend_group' => [self::PARAMETER => $identifiers]]]);

        $this->assertConfigResolverParameterValue(self::PARAMETER, $identifiers, 'ibexa_demo_site');
        $this->assertConfigResolverParameterValue(self::PARAMETER, ['user_group'], 'ibexa_demo_site_admin');
    }

    public function testEmptyListIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['system' => ['default' => [self::PARAMETER => []]]]);
    }
}
