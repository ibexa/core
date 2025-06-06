<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Validator\FileExtensionBlackListValidator;
use Ibexa\Core\FieldType\Value;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Base class for binary field types.
 *
 * @group fieldType
 */
abstract class BinaryBaseTestCase extends FieldTypeTestCase
{
    /** @var string[] */
    protected array $blackListedExtensions = [
        'php',
        'php3',
        'phar',
        'phpt',
        'pht',
        'phtml',
        'pgif',
    ];

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'FileSizeValidator' => [
                'maxFileSize' => [
                    'type' => 'int',
                    'default' => null,
                ],
            ],
        ];
    }

    protected function getConfigResolverMock(): ConfigResolverInterface & MockObject
    {
        $configResolver = $this
            ->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('getParameter')
            ->with('io.file_storage.file_type_blacklist')
            ->willReturn($this->blackListedExtensions);

        return $configResolver;
    }

    protected function getBlackListValidator(): FileExtensionBlackListValidator
    {
        return new FileExtensionBlackListValidator($this->getConfigResolverMock());
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        yield [
            $this->getMockForAbstractClass(Value::class),
            InvalidArgumentException::class,
        ];

        yield [
            ['id' => '/foo/bar'],
            InvalidArgumentException::class,
        ];
    }

    public function provideValidValidatorConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'FileSizeValidator' => [
                        'maxFileSize' => 2342,
                    ],
                ],
            ],
            [
                [
                    'FileSizeValidator' => [
                        'maxFileSize' => null,
                    ],
                ],
            ],
        ];
    }

    public function provideInvalidValidatorConfiguration(): array
    {
        return [
            [
                [
                    'NonExistingValidator' => [],
                ],
            ],
            [
                // maxFileSize must be int or bool
                [
                    'FileSizeValidator' => [
                        'maxFileSize' => 'foo',
                    ],
                ],
            ],
            [
                // maxFileSize is required for this validator
                [
                    'FileSizeValidator' => [],
                ],
            ],
        ];
    }
}
