<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;
use Ibexa\Core\MVC\Symfony\Matcher\ClassNameMatcherFactory;
use Ibexa\Core\MVC\Symfony\Matcher\DynamicallyConfiguredMatcherFactoryDecorator;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DynamicallyConfiguredMatcherFactoryDecoratorTest extends TestCase
{
    private ClassNameMatcherFactory & MockObject $innerMatcherFactory;

    private ConfigResolver & MockObject $configResolver;

    public function setUp(): void
    {
        $innerMatcherFactory = $this->createMock(ClassNameMatcherFactory::class);
        $configResolver = $this->createMock(ConfigResolver::class);

        $this->innerMatcherFactory = $innerMatcherFactory;
        $this->configResolver = $configResolver;
    }

    /**
     * @dataProvider matchConfigProvider
     */
    public function testMatch(string $parameterName, $namespace, $scope, array $viewsConfiguration, array $matchedConfig): void
    {
        $view = $this->createMock(ContentView::class);
        $this->configResolver->expects(self::atLeastOnce())->method('getParameter')->with(
            $parameterName,
            $namespace,
            $scope
        )->willReturn($viewsConfiguration);
        $this->innerMatcherFactory->expects(self::once())->method('match')->with($view)->willReturn($matchedConfig);

        $matcherFactory = new DynamicallyConfiguredMatcherFactoryDecorator(
            $this->innerMatcherFactory,
            $this->configResolver,
            $parameterName,
            $namespace,
            $scope
        );

        self::assertEquals($matchedConfig, $matcherFactory->match($view));
    }

    public function matchConfigProvider(): array
    {
        return [
            [
                'location_view',
                null,
                null,
                [
                    'full' => [
                        'test' => [
                            'template' => 'foo.html.twig',
                            'match' => [
                                \stdClass::class => true,
                            ],
                        ],
                    ],
                ],
                [
                    'template' => 'foo.html.twig',
                    'match' => [
                        \stdClass::class => true,
                    ],
                ],
            ],
        ];
    }
}
