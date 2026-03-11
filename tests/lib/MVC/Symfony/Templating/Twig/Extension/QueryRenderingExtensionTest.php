<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\QueryRenderingExtension;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

final class QueryRenderingExtensionTest extends FileSystemTwigIntegrationTestCase
{
    /**
     * @param array<mixed> $options
     *
     * @return array{
     *     reference: array{
     *         controller: string,
     *         attributes: array<mixed>,
     *         query: array<mixed>,
     *     },
     *     renderer: string,
     *     options: array<mixed>,
     * }
     */
    private static function normalizeRenderArguments(
        ControllerReference $reference,
        string $renderer,
        array $options
    ): array {
        return [
            'reference' => [
                'controller' => $reference->controller,
                'attributes' => $reference->attributes,
                'query' => $reference->query,
            ],
            'renderer' => $renderer,
            'options' => $options,
        ];
    }

    protected function getExtensions(): array
    {
        $fragmentHandler = $this->createMock(FragmentHandler::class);
        $fragmentHandler
            ->method('render')
            ->willReturnCallback(static function (
                ControllerReference $reference,
                string $renderer,
                array $options
            ): string {
                return var_export(self::normalizeRenderArguments($reference, $renderer, $options), true);
            });

        return [
            new QueryRenderingExtension($fragmentHandler),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/_fixtures/query_rendering_functions/';
    }
}
