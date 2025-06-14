<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ConfigResolverTestCase extends TestCase
{
    protected const EXISTING_SA_NAME = 'existing_sa';
    protected const UNDEFINED_SA_NAME = 'undefined_sa';
    protected const SA_GROUP = 'sa_group';

    protected const DEFAULT_NAMESPACE = 'ibexa.site_access.config';

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    protected $siteAccess;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\DependencyInjection\ContainerInterface */
    protected $containerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteAccess = new SiteAccess('test');
        $this->containerMock = $this->createMock(ContainerInterface::class);
    }

    abstract protected function getResolver(string $defaultNamespace = 'ibexa.site_access.config'): ConfigResolverInterface;

    abstract protected function getScope(): string;

    protected function getNamespace(): string
    {
        return self::DEFAULT_NAMESPACE;
    }

    public function testGetParameterFailedWithException(): void
    {
        $resolver = $this->getResolver(self::DEFAULT_NAMESPACE);
        $this->containerMock
            ->expects(self::once())
            ->method('hasParameter')
            ->with(sprintf('%s.%s.undefined', $this->getNamespace(), $this->getScope()))
            ->willReturn(false);

        $this->expectException(ParameterNotFoundException::class);

        $resolver->getParameter('undefined');
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testGetParameterGlobalScope(string $paramName, $expectedValue): void
    {
        $globalScopeParameter = sprintf('%s.%s.%s', $this->getNamespace(), $this->getScope(), $paramName);
        $this->containerMock
            ->expects(self::once())
            ->method('hasParameter')
            ->with($globalScopeParameter)
            ->willReturn(true);
        $this->containerMock
            ->expects(self::once())
            ->method('getParameter')
            ->with($globalScopeParameter)
            ->willReturn($expectedValue);

        self::assertSame($expectedValue, $this->getResolver()->getParameter($paramName));
    }

    public function parameterProvider(): array
    {
        return [
            ['foo', 'bar'],
            ['some.parameter', true],
            ['some.other.parameter', ['foo', 'bar', 'baz']],
            ['a.hash.parameter', ['foo' => 'bar', 'tata' => 'toto']],
            [
                'a.deep.hash', [
                    'foo' => 'bar',
                    'tata' => 'toto',
                    'deeper_hash' => [
                        'likeStarWars' => true,
                        'jedi' => ['Obi-Wan Kenobi', 'Mace Windu', 'Luke Skywalker', 'Leïa Skywalker (yes! Read episodes 7-8-9!)'],
                        'sith' => ['Darth Vader', 'Darth Maul', 'Palpatine'],
                        'roles' => [
                            'Amidala' => ['Queen'],
                            'Palpatine' => ['Senator', 'Emperor', 'Villain'],
                            'C3PO' => ['Droid', 'Annoying guy'],
                            'Jar-Jar' => ['Still wondering his role', 'Annoying guy'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testGetSetDefaultNamespace(): void
    {
        $newDefaultNamespace = 'new';
        $configResolver = $this->getResolver();
        self::assertSame(self::DEFAULT_NAMESPACE, $configResolver->getDefaultNamespace());
        $configResolver->setDefaultNamespace($newDefaultNamespace);
        self::assertSame($newDefaultNamespace, $configResolver->getDefaultNamespace());
    }
}
