<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\IO\MimeTypeDetector;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\Type as ImageType;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator\FileExtensionBlackListValidator;
use Ibexa\Core\FieldType\Validator\ImageValidator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group fieldType
 * @group ibexa_float
 */
class ImageTest extends FieldTypeTestCase
{
    private const MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
    ];

    protected $blackListedExtensions = [
        'php',
        'php3',
        'phar',
        'phpt',
        'pht',
        'phtml',
        'pgif',
    ];

    private MimeTypeDetector & MockObject $mimeTypeDetectorMock;

    public function getImageInputPath(): string
    {
        return __DIR__ . '/../_fixtures/squirrel-developers.jpg';
    }

    protected function getMimeTypeDetectorMock(): MimeTypeDetector & MockObject
    {
        if (!isset($this->mimeTypeDetectorMock)) {
            $this->mimeTypeDetectorMock = $this->createMock(MimeTypeDetector::class);
        }

        return $this->mimeTypeDetectorMock;
    }

    protected function createFieldTypeUnderTest(): ImageType
    {
        $fieldType = new ImageType(
            [
                $this->getBlackListValidator(),
                $this->getImageValidator(),
            ],
            self::MIME_TYPES
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    private function getBlackListValidator(): FileExtensionBlackListValidator
    {
        return new FileExtensionBlackListValidator($this->getConfigResolverMock());
    }

    private function getImageValidator(): ImageValidator
    {
        return new ImageValidator();
    }

    private function getConfigResolverMock(): ConfigResolverInterface & MockObject
    {
        $configResolver = $this
            ->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('getParameter')
            ->with('io.file_storage.file_type_blacklist')
            ->willReturn($this->blackListedExtensions);

        return $configResolver;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'FileSizeValidator' => [
                'maxFileSize' => [
                    'type' => 'numeric',
                    'default' => null,
                ],
            ],
            'AlternativeTextValidator' => [
                'required' => [
                    'type' => 'bool',
                    'default' => false,
                ],
            ],
        ];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [
            'mimeTypes' => [
                'type' => 'choice',
                'default' => [],
            ],
        ];
    }

    protected function getEmptyValueExpectation(): ImageValue
    {
        return new ImageValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                'foo',
                InvalidArgumentException::class,
            ],
            [
                new ImageValue(
                    [
                        'id' => 'non/existent/path',
                    ]
                ),
                InvalidArgumentException::class,
            ],
            [
                new ImageValue(
                    [
                        'id' => __FILE__,
                        'fileName' => [],
                    ]
                ),
                InvalidArgumentException::class,
            ],
            [
                new ImageValue(
                    [
                        'id' => __FILE__,
                        'fileName' => 'ImageTest.php',
                        'fileSize' => 'truebar',
                    ]
                ),
                InvalidArgumentException::class,
            ],
            [
                new ImageValue(
                    [
                        'id' => __FILE__,
                        'fileName' => 'ImageTest.php',
                        'fileSize' => 23,
                        'alternativeText' => [],
                    ]
                ),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new ImageValue(),
        ];

        yield 'empty array' => [
            [],
            new ImageValue(),
        ];

        yield 'empty ImageValue object' => [
            new ImageValue(),
            new ImageValue(),
        ];

        yield 'file path string' => [
            $this->getImageInputPath(),
            new ImageValue(
                [
                    'inputUri' => $this->getImageInputPath(),
                    'fileName' => basename($this->getImageInputPath()),
                    'fileSize' => filesize($this->getImageInputPath()),
                    'alternativeText' => null,
                ]
            ),
        ];

        yield 'array with all fields' => [
            [
                'id' => $this->getImageInputPath(),
                'fileName' => 'Sindelfingen-Squirrels.jpg',
                'fileSize' => 23,
                'alternativeText' => 'This is so Sindelfingen!',
                'uri' => 'http://' . $this->getImageInputPath(),
            ],
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => 'Sindelfingen-Squirrels.jpg',
                    'fileSize' => 23,
                    'alternativeText' => 'This is so Sindelfingen!',
                    'uri' => 'http://' . $this->getImageInputPath(),
                ]
            ),
        ];

        yield 'array with inputUri and custom fields' => [
            [
                'inputUri' => $this->getImageInputPath(),
                'fileName' => 'My Fancy Filename',
                'fileSize' => 123,
            ],
            new ImageValue(
                [
                    'inputUri' => $this->getImageInputPath(),
                    'fileName' => 'My Fancy Filename',
                    'fileSize' => filesize($this->getImageInputPath()),
                ]
            ),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new ImageValue(),
                null,
            ],
            [
                new ImageValue(
                    [
                        'id' => $this->getImageInputPath(),
                        'fileName' => 'Sindelfingen-Squirrels.jpg',
                        'fileSize' => 23,
                        'alternativeText' => 'This is so Sindelfingen!',
                        'imageId' => '123-12345',
                        'uri' => 'http://' . $this->getImageInputPath(),
                        'width' => 123,
                        'height' => 456,
                        'mime' => 'image/jpeg',
                    ]
                ),
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => 'Sindelfingen-Squirrels.jpg',
                    'fileSize' => 23,
                    'alternativeText' => 'This is so Sindelfingen!',
                    'imageId' => '123-12345',
                    'uri' => 'http://' . $this->getImageInputPath(),
                    'inputUri' => null,
                    'width' => 123,
                    'height' => 456,
                    'additionalData' => [],
                    'mime' => 'image/jpeg',
                ],
            ],
            [
                new ImageValue(
                    [
                        'inputUri' => $this->getImageInputPath(),
                        'fileName' => 'Sindelfingen-Squirrels.jpg',
                        'fileSize' => 23,
                        'alternativeText' => 'This is so Sindelfingen!',
                        'imageId' => '123-12345',
                        'uri' => 'http://' . $this->getImageInputPath(),
                        'mime' => null,
                    ]
                ),
                [
                    'id' => null,
                    'fileName' => 'Sindelfingen-Squirrels.jpg',
                    'fileSize' => 23,
                    'alternativeText' => 'This is so Sindelfingen!',
                    'imageId' => '123-12345',
                    'uri' => 'http://' . $this->getImageInputPath(),
                    'inputUri' => $this->getImageInputPath(),
                    'width' => null,
                    'height' => null,
                    'additionalData' => [],
                    'mime' => null,
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new ImageValue(),
            ],
            [
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => 'Sindelfingen-Squirrels.jpg',
                    'fileSize' => 23,
                    'alternativeText' => 'This is so Sindelfingen!',
                    'uri' => 'http://' . $this->getImageInputPath(),
                ],
                new ImageValue(
                    [
                        'id' => $this->getImageInputPath(),
                        'fileName' => 'Sindelfingen-Squirrels.jpg',
                        'fileSize' => 23,
                        'alternativeText' => 'This is so Sindelfingen!',
                        'uri' => 'http://' . $this->getImageInputPath(),
                    ]
                ),
            ],
            // BC with 5.0 (EZP-20948). Path can be used as input instead of ID.
            [
                [
                    'inputUri' => $this->getImageInputPath(),
                    'fileName' => 'Sindelfingen-Squirrels.jpg',
                    'fileSize' => 23,
                    'alternativeText' => 'This is so Sindelfingen!',
                    'uri' => 'http://' . $this->getImageInputPath(),
                ],
                new ImageValue(
                    [
                        'inputUri' => $this->getImageInputPath(),
                        'fileName' => 'Sindelfingen-Squirrels.jpg',
                        'fileSize' => 23,
                        'alternativeText' => 'This is so Sindelfingen!',
                        'uri' => 'http://' . $this->getImageInputPath(),
                    ]
                ),
            ],
            // @todo: Provide REST upload tests
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_image';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [
                new ImageValue(['fileName' => 'Sindelfingen-Squirrels.jpg']),
                'Sindelfingen-Squirrels.jpg',
                [],
                'en_GB',
            ],
            // Alternative text has priority
            [
                new ImageValue(
                    [
                        'fileName' => 'Sindelfingen-Squirrels.jpg',
                        'alternativeText' => 'This is so Sindelfingen!',
                    ]
                ),
                'This is so Sindelfingen!',
                [],
                'en_GB',
            ],
            [
                new ImageValue(
                    [
                        'fileName' => 'Sindelfingen-Squirrels.jpg',
                        'alternativeText' => 'This is so Sindelfingen!',
                    ]
                ),
                'This is so Sindelfingen!',
                [],
                'en_GB',
            ],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'valid image within size limit' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1.0,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => basename($this->getImageInputPath()),
                    'fileSize' => filesize($this->getImageInputPath()),
                    'alternativeText' => null,
                    'uri' => '',
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
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => basename($this->getImageInputPath()),
                    'fileSize' => filesize($this->getImageInputPath()),
                    'alternativeText' => null,
                    'uri' => '',
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

        yield 'not an image file' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'alternativeText' => null,
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                    null,
                    ['%extensionsBlackList%' => implode(', ', $this->blackListedExtensions)],
                    'fileExtensionBlackList'
                ),
                new ValidationError(
                    'A valid image file is required.',
                    null,
                    [],
                    'id'
                ),
            ],
        ];

        yield 'file too large and invalid' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 0.01,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'alternativeText' => null,
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                    null,
                    ['%extensionsBlackList%' => implode(', ', $this->blackListedExtensions)],
                    'fileExtensionBlackList'
                ),
                new ValidationError('A valid image file is required.', null, [], 'id'),
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

        yield 'file is image but has php extension' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => __DIR__ . '/../_fixtures/phppng.php',
                    'fileName' => basename(__DIR__ . '/../_fixtures/phppng.php'),
                    'fileSize' => filesize(__DIR__ . '/../_fixtures/phppng.php'),
                    'alternativeText' => null,
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                    null,
                    ['%extensionsBlackList%' => implode(', ', $this->blackListedExtensions)],
                    'fileExtensionBlackList'
                ),
                new ValidationError(
                    'A valid image file is required.',
                    null,
                    [],
                    'id'
                ),
            ],
        ];

        yield 'file is image but has php extension uppercase' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => __DIR__ . '/../_fixtures/phppng2.PHP',
                    'fileName' => basename(__DIR__ . '/../_fixtures/phppng2.PHP'),
                    'fileSize' => filesize(__DIR__ . '/../_fixtures/phppng2.PHP'),
                    'alternativeText' => null,
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                    null,
                    ['%extensionsBlackList%' => implode(', ', $this->blackListedExtensions)],
                    'fileExtensionBlackList'
                ),
                new ValidationError(
                    'A valid image file is required.',
                    null,
                    [],
                    'id'
                ),
            ],
        ];

        yield 'alternative text missing' => [
            [
                'validatorConfiguration' => [
                    'AlternativeTextValidator' => [
                        'required' => true,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => basename($this->getImageInputPath()),
                    'fileSize' => filesize($this->getImageInputPath()),
                    'alternativeText' => null,
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'Alternative text is required.',
                    null,
                    [],
                    'alternativeText'
                ),
            ],
        ];

        yield 'alternative text empty string' => [
            [
                'validatorConfiguration' => [
                    'AlternativeTextValidator' => [
                        'required' => true,
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => basename($this->getImageInputPath()),
                    'fileSize' => filesize($this->getImageInputPath()),
                    'alternativeText' => '',
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'Alternative text is required.',
                    null,
                    [],
                    'alternativeText'
                ),
            ],
        ];

        yield 'disallowed mime type' => [
            [
                'fieldSettings' => [
                    'mimeTypes' => [
                        'image/png',
                        'image/gif',
                    ],
                ],
            ],
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => basename($this->getImageInputPath()),
                    'fileSize' => filesize($this->getImageInputPath()),
                    'alternativeText' => '',
                    'uri' => '',
                ]
            ),
            [
                new ValidationError(
                    'The mime type of the file is invalid (%mimeType%). Allowed mime types are %mimeTypes%.',
                    null,
                    [
                        '%mimeType%' => 'image/jpeg',
                        '%mimeTypes%' => 'image/png, image/gif',
                    ],
                    'id'
                ),
            ],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function provideInputForValuesEqual(): iterable
    {
        yield [
            [
                'id' => $this->getImageInputPath(),
                'fileName' => 'Sindelfingen-Squirrels.jpg',
                'fileSize' => 23,
                'alternativeText' => 'This is so Sindelfingen!',
                'imageId' => '123-12345',
                'uri' => 'http://' . $this->getImageInputPath(),
                'width' => 123,
                'height' => 456,
            ],
            new ImageValue(
                [
                    'id' => $this->getImageInputPath(),
                    'fileName' => 'Sindelfingen-Squirrels.jpg',
                    'fileSize' => 23,
                    'alternativeText' => 'This is so Sindelfingen!',
                    'imageId' => '123-12317',
                    'uri' => 'http://' . $this->getImageInputPath(),
                    'inputUri' => null,
                    'width' => 123,
                    'height' => 456,
                ]
            ),
        ];
    }
}
