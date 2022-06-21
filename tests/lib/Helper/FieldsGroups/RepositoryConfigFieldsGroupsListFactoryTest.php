<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Helper\FieldsGroups;

use Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Helper\FieldsGroups\RepositoryConfigFieldsGroupsListFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RepositoryConfigFieldsGroupsListFactoryTest extends TestCase
{
    private MockObject $configResolverMock;

    private $translatorMock;

    public function testBuild()
    {
        $this->getConfigResolverMock()
            ->method('getParameter')
            ->willReturnCallback(static function (string $paramName) {
                switch ($paramName) {
                    case 'content.field_groups.default':
                        return 'group_a';
                    case 'content.field_groups.list':
                        return ['group_a', 'group_b'];
                    default:
                        return null;
                }
            });

        $this->getTranslatorMock()
            ->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $factory = new RepositoryConfigFieldsGroupsListFactory($this->getConfigResolverMock());
        $list = $factory->build($this->getTranslatorMock());

        self::assertEquals(['group_a' => 'group_a', 'group_b' => 'group_b'], $list->getGroups());
        self::assertEquals('group_a', $list->getDefaultGroup());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface
     */
    protected function getConfigResolverMock(): MockObject
    {
        if (!isset($this->configResolverMock)) {
            $this->configResolverMock = $this->createMock(ConfigResolverInterface::class);
        }

        return $this->configResolverMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Contracts\Translation\TranslatorInterface
     */
    protected function getTranslatorMock()
    {
        if (!isset($this->translatorMock)) {
            $this->translatorMock = $this->createMock(TranslatorInterface::class);
        }

        return $this->translatorMock;
    }
}

class_alias(RepositoryConfigFieldsGroupsListFactoryTest::class, 'eZ\Publish\Core\Helper\Tests\FieldsGroups\RepositoryConfigFieldsGroupsListFactoryTest');
