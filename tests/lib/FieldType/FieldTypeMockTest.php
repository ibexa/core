<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\FieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldTypeMockTest extends TestCase
{
    public function testApplyDefaultSettingsThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var FieldType|MockObject $stub */
        $stub = $this->getMockForAbstractClass(
            FieldType::class,
            [],
            '',
            false
        );

        $fieldSettings = new \DateTime();

        $stub->applyDefaultSettings($fieldSettings);
    }

    /**
     * @dataProvider providerForTestApplyDefaultSettings
     *
     * @covers \Ibexa\Core\FieldType\FieldType::applyDefaultSettings
     */
    public function testApplyDefaultSettings(
        mixed $initialSettings,
        mixed $expectedSettings
    ): void {
        /** @var FieldType|MockObject $stub */
        $stub = $this->getMockForAbstractClass(
            FieldType::class,
            [],
            '',
            false,
            true,
            true,
            ['getSettingsSchema']
        );

        $stub
            ->method('getSettingsSchema')
            ->willReturn(
                [
                    'true' => [
                        'default' => true,
                    ],
                    'false' => [
                        'default' => false,
                    ],
                    'null' => [
                        'default' => null,
                    ],
                    'zero' => [
                        'default' => 0,
                    ],
                    'int' => [
                        'default' => 42,
                    ],
                    'float' => [
                        'default' => 42.42,
                    ],
                    'string' => [
                        'default' => 'string',
                    ],
                    'emptystring' => [
                        'default' => '',
                    ],
                    'emptyarray' => [
                        'default' => [],
                    ],
                    'nodefault' => [],
                ]
            );

        $fieldSettings = $initialSettings;
        $stub->applyDefaultSettings($fieldSettings);
        self::assertSame(
            $expectedSettings,
            $fieldSettings
        );
    }

    /**
     * @return iterable<array{
     *     array<string, mixed>,
     *     array<string, mixed>
     * }>
     */
    public static function providerForTestApplyDefaultSettings(): iterable
    {
        yield [
            [],
            [
                'true' => true,
                'false' => false,
                'null' => null,
                'zero' => 0,
                'int' => 42,
                'float' => 42.42,
                'string' => 'string',
                'emptystring' => '',
                'emptyarray' => [],
            ],
        ];
        yield [
            [
                'true' => 'foo',
            ],
            [
                'true' => 'foo',
                'false' => false,
                'null' => null,
                'zero' => 0,
                'int' => 42,
                'float' => 42.42,
                'string' => 'string',
                'emptystring' => '',
                'emptyarray' => [],
            ],
        ];
        yield [
            [
                'null' => 'foo',
            ],
            [
                'null' => 'foo',
                'true' => true,
                'false' => false,
                'zero' => 0,
                'int' => 42,
                'float' => 42.42,
                'string' => 'string',
                'emptystring' => '',
                'emptyarray' => [],
            ],
        ];
        yield [
            $array = [
                'false' => true,
                'emptystring' => ['foo'],
                'null' => 'notNull',
                'additionalEntry' => 'baz',
                'zero' => 10,
                'int' => 'this is not an int',
                'string' => null,
                'emptyarray' => [[]],
                'true' => false,
                'float' => true,
            ],
            $array,
        ];
    }

    public function testApplyDefaultValidatorConfigurationEmptyThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var FieldType|MockObject $stub */
        $stub = $this->getMockForAbstractClass(
            FieldType::class,
            [],
            '',
            false
        );

        $validatorConfiguration = new \DateTime();

        $stub->applyDefaultValidatorConfiguration($validatorConfiguration);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testApplyDefaultValidatorConfigurationEmpty(): void
    {
        /** @var FieldType|MockObject $stub */
        $stub = $this->getMockForAbstractClass(
            FieldType::class,
            [],
            '',
            false,
            true,
            true,
            ['getValidatorConfigurationSchema']
        );

        $stub
            ->method('getValidatorConfigurationSchema')
            ->willReturn(
                []
            );

        $validatorConfiguration = null;
        $stub->applyDefaultValidatorConfiguration($validatorConfiguration);
        self::assertNull(
            $validatorConfiguration
        );
    }

    /**
     * @dataProvider providerForTestApplyDefaultValidatorConfiguration
     *
     * @throws InvalidArgumentException
     */
    public function testApplyDefaultValidatorConfiguration(
        mixed $initialConfiguration,
        mixed $expectedConfiguration
    ): void {
        /** @var FieldType|MockObject $stub */
        $stub = $this->getMockForAbstractClass(
            FieldType::class,
            [],
            '',
            false,
            true,
            true,
            ['getValidatorConfigurationSchema']
        );

        $stub
            ->method('getValidatorConfigurationSchema')
            ->willReturn(
                [
                    'TestValidator' => [
                        'valueClick' => [
                            'default' => 1,
                        ],
                        'valueClack' => [
                            'default' => 0,
                        ],
                    ],
                ]
            );

        $validatorConfiguration = $initialConfiguration;
        $stub->applyDefaultValidatorConfiguration($validatorConfiguration);
        self::assertSame(
            $expectedConfiguration,
            $validatorConfiguration
        );
    }

    /**
     * @return iterable<array{
     *     null|array<string, mixed>,
     *     array<string, mixed>
     * }>
     */
    public function providerForTestApplyDefaultValidatorConfiguration(): iterable
    {
        $defaultConfiguration = [
            'TestValidator' => [
                'valueClick' => 1,
                'valueClack' => 0,
            ],
        ];

        yield [
            null,
            $defaultConfiguration,
        ];
        yield [
            [],
            $defaultConfiguration,
        ];
        yield [
            [
                'TestValidator' => [
                    'valueClick' => 100,
                ],
            ],
            [
                'TestValidator' => [
                    'valueClick' => 100,
                    'valueClack' => 0,
                ],
            ],
        ];
    }
}
