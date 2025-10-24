<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\IsPreview;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\View;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\IsPreview
 */
final class IsPreviewTest extends TestCase
{
    private IsPreview $isPreviewMatcher;

    protected function setUp(): void
    {
        $this->isPreviewMatcher = new IsPreview();
    }

    /**
     * @return iterable<string, array{View, bool, bool}>
     */
    public static function getDataForTestMatch(): iterable
    {
        $previewContentView = new ContentView();
        $previewContentView->setParameters(['isPreview' => true]);

        $notPreviewContentView = new ContentView();
        $notPreviewContentView->setParameters(['isPreview' => false]);

        $viewContentView = new ContentView();
        yield 'match for preview content view' => [
            $previewContentView,
            true, // IsPreview: true
            true, // matches the view
        ];

        yield 'do not match for preview content view' => [
            $previewContentView,
            false, // IsPreview: false
            false, // doesn't match the view
        ];

        yield 'match for view content view' => [
            $notPreviewContentView,
            false, // IsPreview: false
            true, // matches since it's not a preview content view
        ];

        yield 'do not match for view content view' => [
            $notPreviewContentView,
            true, // IsPreview: true
            false, // doesn't match since it's not a preview content view
        ];

        yield 'not match for not set isPreview parameter' => [
            $viewContentView,
            true, // IsPreview: true
            false, // by default, it's not a preview view if parameter is not set
        ];

        yield 'do not match for not set isPreview parameter' => [
            $viewContentView,
            false,
            true, // matches not a preview view, when parameter is not set
        ];
    }

    /**
     * @dataProvider getDataForTestMatch
     *
     * @throws InvalidArgumentException
     */
    public function testMatch(
        View $view,
        bool $matchConfig,
        bool $expectedIsPreview
    ): void {
        $this->isPreviewMatcher->setMatchingConfig($matchConfig);

        self::assertSame($expectedIsPreview, $this->isPreviewMatcher->match($view));
    }

    public function testSetMatchConfigThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IsPreview matcher expects true or false value, got a value of integer type');
        $this->isPreviewMatcher->setMatchingConfig(123);
    }
}
