<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper\FieldsGroups;

use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Core\Helper\FieldsGroups\RepositoryConfigFieldsGroupsListFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RepositoryConfigFieldsGroupsListFactoryTest extends TestCase
{
    private RepositoryConfigurationProviderInterface & MockObject $repositoryConfigMock;

    private TranslatorInterface & MockObject $translatorMock;

    public function testBuild(): void
    {
        $this->getRepositoryConfigMock()
            ->expects(self::once())
            ->method('getRepositoryConfig')
            ->willReturn(['fields_groups' => ['list' => ['group_a', 'group_b'], 'default' => 'group_a']]);

        $this->getTranslatorMock()
            ->method('trans')
            ->willReturnArgument(0);

        $factory = new RepositoryConfigFieldsGroupsListFactory($this->getRepositoryConfigMock());
        $list = $factory->build($this->getTranslatorMock());

        self::assertEquals(['group_a' => 'group_a', 'group_b' => 'group_b'], $list->getGroups());
        self::assertEquals('group_a', $list->getDefaultGroup());
    }

    protected function getRepositoryConfigMock(): RepositoryConfigurationProviderInterface & MockObject
    {
        if (!isset($this->repositoryConfigMock)) {
            $this->repositoryConfigMock = $this->createMock(RepositoryConfigurationProviderInterface::class);
        }

        return $this->repositoryConfigMock;
    }

    protected function getTranslatorMock(): TranslatorInterface & MockObject
    {
        if (!isset($this->translatorMock)) {
            $this->translatorMock = $this->createMock(TranslatorInterface::class);
        }

        return $this->translatorMock;
    }
}
