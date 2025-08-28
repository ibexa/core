<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Base\Container\Compiler\Search\Legacy;

use Ibexa\Core\Base\Container\Compiler\Search\Legacy\CriteriaConverterPass;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CriteriaConverterPassTest extends AbstractCompilerPassTestCase
{
    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CriteriaConverterPass());
    }

    /**
     * @dataProvider provideDescribedServiceToTagName
     */
    public function testAddHandlers(string $serviceId, string $tag): void
    {
        $this->setDefinition(
            $serviceId,
            new Definition()
        );

        $def = new Definition();
        $def->addTag($tag);
        $this->setDefinition('service_id', $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            $serviceId,
            'addHandler',
            [new Reference('service_id')]
        );
    }

    /**
     * @dataProvider provideDescribedServiceToTagName
     */
    public function testAddContentHandlersWithPriority(string $serviceId, string $tag): void
    {
        $this->setDefinition(
            $serviceId,
            new Definition()
        );

        $def = new Definition();
        $def->addTag($tag, ['priority' => 0]);
        $this->setDefinition('service_1_id', $def);

        $def = new Definition();
        $def->addTag($tag, ['priority' => 100]);
        $this->setDefinition('service_with_priority', $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            $serviceId,
            'addHandler',
            [new Reference('service_with_priority')],
            0,
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            $serviceId,
            'addHandler',
            [new Reference('service_1_id')],
            1,
        );
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideServiceToTagName(): iterable
    {
        yield [
            'ibexa.search.legacy.gateway.criteria_converter.content',
            'ibexa.search.legacy.gateway.criterion_handler.content',
        ];

        yield [
            'ibexa.search.legacy.gateway.criteria_converter.location',
            'ibexa.search.legacy.gateway.criterion_handler.location',
        ];

        yield [
            'ibexa.core.trash.search.legacy.gateway.criteria_converter',
            'ibexa.search.legacy.trash.gateway.criterion.handler',
        ];

        yield [
            CriteriaConverter::class,
            'ibexa.storage.legacy.url.criterion.handler',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideDescribedServiceToTagName(): iterable
    {
        foreach (self::provideServiceToTagName() as $serviceToTagName) {
            yield sprintf('Service "%s" with tag "%s"', $serviceToTagName[0], $serviceToTagName[1]) => $serviceToTagName;
        }
    }

    public function testAddMultipleHandlers(): void
    {
        $this->setDefinition(
            'ibexa.search.legacy.gateway.criteria_converter.content',
            new Definition()
        );
        $this->setDefinition(
            'ibexa.search.legacy.gateway.criteria_converter.location',
            new Definition()
        );
        $this->setDefinition(
            'ibexa.core.trash.search.legacy.gateway.criteria_converter',
            new Definition()
        );

        $commonServiceId = 'common_service_id';
        $def = new Definition();
        $def->addTag('ibexa.search.legacy.gateway.criterion_handler.content');
        $def->addTag('ibexa.search.legacy.gateway.criterion_handler.location');
        $def->addTag('ibexa.search.legacy.trash.gateway.criterion.handler');
        $this->setDefinition($commonServiceId, $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.search.legacy.gateway.criteria_converter.content',
            'addHandler',
            [new Reference($commonServiceId)]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.search.legacy.gateway.criteria_converter.location',
            'addHandler',
            [new Reference($commonServiceId)]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.core.trash.search.legacy.gateway.criteria_converter',
            'addHandler',
            [new Reference($commonServiceId)]
        );
    }
}

class_alias(CriteriaConverterPassTest::class, 'eZ\Publish\Core\Base\Tests\Container\Compiler\Search\Legacy\CriteriaConverterPassTest');
