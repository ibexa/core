<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Contracts\Rector\Sets\IbexaSetList;
use Ibexa\Rector\Rule\PropertyToGetterRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony61\Rector\Class_\CommandConfigureToAttributeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        IbexaSetList::IBEXA_50->value,
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
        SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_70,
        SymfonySetList::SYMFONY_71,
        SymfonySetList::SYMFONY_72,
        DoctrineSetList::DOCTRINE_DBAL_211,
    ])
    ->withSkip([
        CommandConfigureToAttributeRector::class => [
            __DIR__ . '/tests/bundle/Core/EventListener/BackwardCompatibleCommandListenerTest.php',
        ],
        PropertyToGetterRector::class => [
            __DIR__ . '/tests/lib/Repository/Values/ContentType/ContentTypeTest.php',
        ]
    ]);
