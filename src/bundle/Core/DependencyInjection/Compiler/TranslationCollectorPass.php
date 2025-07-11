<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\Translation\GlobCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationCollectorPass implements CompilerPassInterface
{
    public const string ORIGINAL_TRANSLATION = 'en';

    public const array LOCALES_MAP = [
        'de_DE' => 'de',
        'el_GR' => 'el',
        'es_ES' => 'es',
        'fi_FI' => 'fi',
        'fr_FR' => 'fr',
        'hi_IN' => 'hi',
        'hr_HR' => 'hr',
        'hu_HU' => 'hu',
        'it_IT' => 'it',
        'ja_JP' => 'ja',
        'nb_NO' => 'nb',
        'pl_PL' => 'pl',
        'pt_PT' => 'pt',
        'ru_RU' => 'ru',
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('translator.default')) {
            return;
        }

        $collector = new GlobCollector($container->getParameterBag()->get('kernel.project_dir'));

        $availableTranslations = [self::ORIGINAL_TRANSLATION];

        if ($container->getParameter('ibexa.ui.translations.enabled')) {
            foreach ($collector->collect() as $file) {
                /* TODO - to remove when translation files will have proper names. */
                if (isset(self::LOCALES_MAP[$file['locale']])) {
                    $file['locale'] = self::LOCALES_MAP[$file['locale']];
                }
                $availableTranslations[] = $file['locale'];
            }
        }

        $container->setParameter('available_translations', array_values(array_unique($availableTranslations)));
    }
}
