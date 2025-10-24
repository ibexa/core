<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Matcher;

use Ibexa\Bundle\Core\Matcher\ServiceAwareMatcherFactory;
use Ibexa\Contracts\Core\MVC\View\ViewMatcherRegistryInterface;
use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\View;
use Ibexa\Core\Repository\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Matcher\ServiceAwareMatcherFactory
 *
 * @phpstan-type TMatchConfigArray array<string, array<string, array{'match': array<string, mixed>}>>
 */
final class ServiceAwareMatcherFactoryTest extends TestCase
{
    /** @var ViewMatcherRegistryInterface&MockObject */
    private ViewMatcherRegistryInterface $viewMatcherRegistryMock;

    /** @var ViewMatcherInterface&MockObject */
    private ViewMatcherInterface $matcherMock;

    protected function setUp(): void
    {
        $this->viewMatcherRegistryMock = $this->createMock(ViewMatcherRegistryInterface::class);
        $this->viewMatcherRegistryMock->method('hasMatcher')->willReturnMap(
            [
                ['App\Matcher', true],
                ['IdentifierBasedMatcher', true],
            ]
        );

        $this->matcherMock = $this->createMock(ViewMatcherInterface::class);

        $this->viewMatcherRegistryMock->method('getMatcher')->willReturnMap(
            [
                ['App\Matcher', $this->matcherMock],
                ['IdentifierBasedMatcher', $this->matcherMock],
            ]
        );
    }

    /**
     * @phpstan-return iterable<string, array{View, TMatchConfigArray, string}>
     */
    public function getDataForTestMatch(): iterable
    {
        yield 'full view service-based matcher' => [
            new ContentView(),
            [
                'full' => [
                    'my_view' => [
                        'match' => [
                            '@App\Matcher' => 'service-based config',
                        ],
                    ],
                ],
            ],
            'service-based config',
        ];

        yield 'full view identifier-based matcher' => [
            new ContentView(),
            [
                'full' => [
                    'my_view' => [
                        'match' => [
                            'IdentifierBasedMatcher' => 'identifier-based config',
                        ],
                    ],
                ],
            ],
            'identifier-based config',
        ];
    }

    /**
     * @dataProvider getDataForTestMatch
     *
     * @phpstan-param TMatchConfigArray $matchConfig
     */
    public function testMatch(
        View $view,
        array $matchConfig,
        string $matchedConfigValue
    ): void {
        $serviceMatcherFactory = new ServiceAwareMatcherFactory(
            $this->viewMatcherRegistryMock,
            $this->createMock(Repository::class),
            null,
            $matchConfig
        );
        $this->matcherMock->method('setMatchingConfig')->with(true);
        $this->matcherMock->method('match')->with($view)->willReturn($matchedConfigValue);

        self::assertSame(
            [
                'match' => $matchConfig['full']['my_view']['match'],
                'matcher' => $this->matcherMock,
            ],
            $serviceMatcherFactory->match($view)
        );
    }
}
