<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\BinaryBase\RouteAwarePathGenerator;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\FieldType\BinaryFile\Type as BinaryFileType;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_binaryfile
 *
 * @covers \Ibexa\Core\FieldType\BinaryFile\Type
 */
class BinaryFileTest extends BinaryBaseTestCase
{
    protected function createFieldTypeUnderTest(): BinaryFileType
    {
        $fieldType = new BinaryFileType(
            [$this->getBlackListValidator()],
            $this->getRouteAwarePathGenerator()
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getEmptyValueExpectation(): BinaryFileValue
    {
        return new BinaryFileValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        yield from parent::provideInvalidInputForAcceptValue();

        yield [
                new BinaryFileValue(['id' => '/foo/bar']),
                InvalidArgumentValue::class,
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new BinaryFileValue(),
        ];

        yield 'empty array' => [
            [],
            new BinaryFileValue(),
        ];

        yield 'empty BinaryFileValue object' => [
            new BinaryFileValue(),
            new BinaryFileValue(),
        ];

        yield 'file path string' => [
            __FILE__,
            new BinaryFileValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'downloadCount' => 0,
                    'mimeType' => null,
                ]
            ),
        ];

        yield 'array with inputUri' => [
            ['inputUri' => __FILE__],
            new BinaryFileValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'downloadCount' => 0,
                    'mimeType' => null,
                ]
            ),
        ];

        yield 'array with inputUri and fileSize' => [
            [
                'inputUri' => __FILE__,
                'fileSize' => 23,
            ],
            new BinaryFileValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => 23,
                    'downloadCount' => 0,
                    'mimeType' => null,
                ]
            ),
        ];

        yield 'array with inputUri and mimeType' => [
            [
                'inputUri' => __FILE__,
                'mimeType' => 'application/text+php',
            ],
            new BinaryFileValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'downloadCount' => 0,
                    'mimeType' => 'application/text+php',
                ]
            ),
        ];

        yield 'array with inputUri and downloadCount' => [
            [
                'inputUri' => __FILE__,
                'downloadCount' => 42,
            ],
            new BinaryFileValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'downloadCount' => 42,
                    'mimeType' => null,
                ]
            ),
        ];

        yield 'BC with 5.2 - id instead of inputUri' => [
            ['id' => __FILE__],
            new BinaryFileValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'downloadCount' => 0,
                    'mimeType' => null,
                ]
            ),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new BinaryFileValue(),
                null,
            ],
            [
                new BinaryFileValue(
                    [
                        'id' => 'some/file/here',
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => 'http://some/file/here',
                    ]
                ),
                [
                    'id' => 'some/file/here',
                    'inputUri' => null,
                    'path' => null,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'http://some/file/here',
                ],
            ],
            [
                new BinaryFileValue(
                    [
                        'inputUri' => 'some/file/here',
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => 'http://some/file/here',
                    ]
                ),
                [
                    'id' => null,
                    'inputUri' => 'some/file/here',
                    // Used for BC with 5.0 (EZP-20948)
                    'path' => 'some/file/here',
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'http://some/file/here',
                ],
            ],
            // BC with 5.0 (EZP-20948). Path can be used as input instead of inputUri.
            [
                new BinaryFileValue(
                    [
                        'path' => 'some/file/here',
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => 'http://some/file/here',
                    ]
                ),
                [
                    'id' => 'some/file/here',
                    'inputUri' => null,
                    'path' => null,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'http://some/file/here',
                ],
            ],
            // BC with 5.0 (EZP-20948). Path can be used as input instead of inputUri.
            [
                new BinaryFileValue(
                    [
                        'path' => __FILE__,
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => 'http://some/file/here',
                    ]
                ),
                [
                    'id' => null,
                    'inputUri' => __FILE__,
                    'path' => __FILE__,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'http://some/file/here',
                ],
            ],
            // BC with 5.2 (EZP-22808). Id can be used as input instead of inputUri.
            [
                new BinaryFileValue(
                    [
                        'id' => __FILE__,
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => 'http://some/file/here',
                    ]
                ),
                [
                    'id' => null,
                    'inputUri' => __FILE__,
                    'path' => __FILE__,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'http://some/file/here',
                ],
            ],
            // BC with 5.2 (EZP-22808). Id is recognized as such if not pointing to existing file.
            [
                new BinaryFileValue(
                    [
                        'id' => 'application/asdf1234.pdf',
                        'fileName' => 'asdf1234.pdf',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'application/pdf',
                        'uri' => 'http://some/file/here',
                    ]
                ),
                [
                    'id' => 'application/asdf1234.pdf',
                    'inputUri' => null,
                    'path' => null,
                    'fileName' => 'asdf1234.pdf',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'application/pdf',
                    'uri' => 'http://some/file/here',
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new BinaryFileValue(),
            ],
            [
                [
                    'id' => 'some/file/here',
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ],
                new BinaryFileValue(
                    [
                        'id' => 'some/file/here',
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                    ]
                ),
            ],
            // BC with 5.0 (EZP-20948). Path can be used as input instead of inputUri.
            [
                [
                    'path' => 'some/file/here',
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ],
                new BinaryFileValue(
                    [
                        'id' => 'some/file/here',
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                    ]
                ),
            ],
            // BC with 5.2 (EZP-22808). Id can be used as input instead of inputUri.
            [
                [
                    'id' => __FILE__,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ],
                new BinaryFileValue(
                    [
                        'id' => null,
                        'inputUri' => __FILE__,
                        'path' => __FILE__,
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                    ]
                ),
            ],
            [
                [
                    'id' => __FILE__,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'some_uri_acquired_from_SPI',
                    'route' => 'some_route',
                ],
                new BinaryFileValue(
                    [
                        'id' => null,
                        'inputUri' => __FILE__,
                        'path' => __FILE__,
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => '__GENERATED_URI__',
                    ]
                ),
            ],
            [
                [
                    'id' => __FILE__,
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                    'uri' => 'some_uri_acquired_from_SPI',
                    'route' => 'some_route',
                    'route_parameters' => [
                        'any_param' => true,
                    ],
                ],
                new BinaryFileValue(
                    [
                        'id' => null,
                        'inputUri' => __FILE__,
                        'path' => __FILE__,
                        'fileName' => 'sindelfingen.jpg',
                        'fileSize' => 2342,
                        'downloadCount' => 0,
                        'mimeType' => 'image/jpeg',
                        'uri' => '__GENERATED_URI_WITH_PARAMS__',
                    ]
                ),
            ],
            // @todo: Provide upload struct (via REST)!
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_binaryfile';
    }

    public function provideDataForGetName(): array
    {
        return [
            [new BinaryFileValue(), '', [], 'en_GB'],
            [new BinaryFileValue(['fileName' => 'sindelfingen.jpg']), 'sindelfingen.jpg', [], 'en_GB'],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'valid file size' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new BinaryFileValue(
                [
                    'id' => 'some/file/here',
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 2342,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ]
            ),
        ];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield 'file too large' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 0.01,
                    ],
                ],
            ],
            new BinaryFileValue(
                [
                    'id' => 'some/file/here',
                    'fileName' => 'sindelfingen.jpg',
                    'fileSize' => 150000,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ]
            ),
            [
                new ValidationError(
                    'The file size cannot exceed %size% megabyte.',
                    'The file size cannot exceed %size% megabytes.',
                    [
                        '%size%' => 0.01,
                    ],
                    'fileSize'
                ),
            ],
        ];

        yield 'blacklisted file extension' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new BinaryFileValue(
                [
                    'id' => 'phppng.php',
                    'fileName' => 'phppng.php',
                    'fileSize' => 0.01,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ]
            ),
            [
                new ValidationError(
                    'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                    null,
                    ['%extensionsBlackList%' => implode(', ', $this->blackListedExtensions)],
                    'fileExtensionBlackList'
                ),
            ],
        ];

        yield 'blacklisted file extension uppercase' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new BinaryFileValue(
                [
                    'id' => 'phppng.PHP',
                    'fileName' => 'phppng.PHP',
                    'fileSize' => 0.01,
                    'downloadCount' => 0,
                    'mimeType' => 'image/jpeg',
                ]
            ),
            [
                new ValidationError(
                    'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                    null,
                    ['%extensionsBlackList%' => implode(', ', $this->blackListedExtensions)],
                    'fileExtensionBlackList'
                ),
            ],
        ];
    }

    private function getRouteAwarePathGenerator(): RouteAwarePathGenerator
    {
        $mock = $this->createMock(RouteAwarePathGenerator::class);
        $mock->method('generate')
            ->willReturnCallback(static function (string $route, array $routeParameters = []): string {
                if ($routeParameters) {
                    return '__GENERATED_URI_WITH_PARAMS__';
                }

                return '__GENERATED_URI__';
            });

        return $mock;
    }
}
