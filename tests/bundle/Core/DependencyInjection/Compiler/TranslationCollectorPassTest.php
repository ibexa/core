<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\TranslationCollectorPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TranslationCollectorPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TranslationCollectorPass());
    }

    /**
     * @dataProvider translationCollectorProvider
     */
    public function testTranslationCollector(
        bool $translationsEnabled,
        array $availableTranslations
    ): void {
        $this->setDefinition('translator.default', new Definition());
        $this->setParameter('kernel.project_dir', __DIR__ . $this->normalizePath('/../Fixtures'));
        $this->setParameter('ibexa.ui.translations.enabled', $translationsEnabled);

        $this->compile();

        $this->assertContainerBuilderHasParameter('available_translations', $availableTranslations);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('/', \DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @return iterable<string,array{bool,array{string}}>
     */
    public function translationCollectorProvider(): iterable
    {
        yield 'translations enabled' => [
            true,
            ['en', 'hi', 'nb'],
        ];

        yield 'translations disabled' => [
            false,
            ['en'],
        ];
    }
}
