<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\IO\MimeTypeDetector;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\Type as ImageType;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator\FileExtensionBlackListValidator;
use Ibexa\Core\FieldType\Validator\ImageValidator;

/**
 * @group fieldType
 * @group ezfloat
 */
class ImageTest extends FieldTypeTest
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

    public function getImageInputPath(): string
    {
        return __DIR__ . '/../_fixtures/squirrel-developers.jpg';
    }

    /**
     * @return \Ibexa\Contracts\Core\IO\MimeTypeDetector
     */
    protected function getMimeTypeDetectorMock()
    {
        if (!isset($this->mimeTypeDetectorMock)) {
            $this->mimeTypeDetectorMock = $this->createMock(MimeTypeDetector::class);
        }

        return $this->mimeTypeDetectorMock;
    }

    /**
     * Returns the field type under test.
     *
     * This method is used by all test cases to retrieve the field type under
     * test. Just create the FieldType instance using mocks from the provided
     * get*Mock() methods and/or custom get*Mock() implementations. You MUST
     * NOT take care for test case wide caching of the field type, just return
     * a new instance from this method!
     *
     * @return \Ibexa\Core\FieldType\FieldType
     */
    protected function createFieldTypeUnderTest()
    {
        $fieldType = new ImageType(
            [
                $this->getBlackListValidatorMock(),
                $this->getImageValidatorMock(),
            ],
            self::MIME_TYPES
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    private function getBlackListValidatorMock()
    {
        return $this
            ->getMockBuilder(FileExtensionBlackListValidator::class)
            ->setConstructorArgs([
                $this->getConfigResolverMock(),
            ])
            ->setMethods(null)
            ->getMock();
    }

    private function getImageValidatorMock()
    {
        return $this
            ->getMockBuilder(ImageValidator::class)
            ->setMethods(null)
            ->getMock();
    }

    private function getConfigResolverMock()
    {
        $configResolver = $this
            ->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('getParameter')
            ->with('io.file_storage.file_type_blacklist')
            ->willReturn($this->blackListedExtensions);

        return $configResolver;
    }

    /**
     * Returns the validator configuration schema expected from the field type.
     *
     * @return array
     */
    protected function getValidatorConfigurationSchemaExpectation()
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

    /**
     * Returns the settings schema expected from the field type.
     *
     * @return array
     */
    protected function getSettingsSchemaExpectation()
    {
        return [
            'mimeTypes' => [
                'type' => 'choice',
                'default' => [],
            ],
        ];
    }

    /**
     * Returns the empty value expected from the field type.
     *
     * @return \Ibexa\Core\FieldType\Image\Value
     */
    protected function getEmptyValueExpectation()
    {
        return new ImageValue();
    }

    public function provideInvalidInputForAcceptValue()
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

    /**
     * Data provider for valid input to acceptValue().
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to acceptValue(), 2. The expected return value from acceptValue().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          __FILE__,
     *          new BinaryFileValue( array(
     *              'id' => __FILE__,
     *              'fileName' => basename( __FILE__ ),
     *              'fileSize' => filesize( __FILE__ ),
     *              'downloadCount' => 0,
     *              'mimeType' => 'text/plain',
     *          ) )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidInputForAcceptValue()
    {
        return [
            [
                null,
                new ImageValue(),
            ],
            [
                [],
                new ImageValue(),
            ],
            [
                new ImageValue(),
                new ImageValue(),
            ],
            [
                $this->getImageInputPath(),
                new ImageValue(
                    [
                        'inputUri' => $this->getImageInputPath(),
                        'fileName' => basename($this->getImageInputPath()),
                        'fileSize' => filesize($this->getImageInputPath()),
                        'alternativeText' => null,
                    ]
                ),
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
            [
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
            ],
        ];
    }

    /**
     * Provide input for the toHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to toHash(), 2. The expected return value from toHash().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          new BinaryFileValue( array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ) ),
     *          array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInputForToHash()
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

    /**
     * Provide input to fromHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to fromHash(), 2. The expected return value from fromHash().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ),
     *          new BinaryFileValue( array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ) )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInputForFromHash()
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
        return 'ezimage';
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

    /**
     * Provides data sets with validator configuration and/or field settings and
     * field value which are considered valid by the {@link validate()} method.
     *
     * ATTENTION: This is a default implementation, which must be overwritten if
     * a FieldType supports validation!
     *
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(
     *              "validatorConfiguration" => array(
     *                  "StringLengthValidator" => array(
     *                      "minStringLength" => 2,
     *                      "maxStringLength" => 10,
     *                  ),
     *              ),
     *          ),
     *          new TextLineValue( "lalalala" ),
     *      ),
     *      array(
     *          array(
     *              "fieldSettings" => array(
     *                  'isMultiple' => true
     *              ),
     *          ),
     *          new CountryValue(
     *              array(
     *                  "BE" => array(
     *                      "Name" => "Belgium",
     *                      "Alpha2" => "BE",
     *                      "Alpha3" => "BEL",
     *                      "IDC" => 32,
     *                  ),
     *              ),
     *          ),
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidDataForValidate()
    {
        return [
            [
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
            ],
        ];
    }

    /**
     * Provides data sets with validator configuration and/or field settings,
     * field value and corresponding validation errors returned by
     * the {@link validate()} method.
     *
     * ATTENTION: This is a default implementation, which must be overwritten
     * if a FieldType supports validation!
     *
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(
     *              "validatorConfiguration" => array(
     *                  "IntegerValueValidator" => array(
     *                      "minIntegerValue" => 5,
     *                      "maxIntegerValue" => 10
     *                  ),
     *              ),
     *          ),
     *          new IntegerValue( 3 ),
     *          array(
     *              new ValidationError(
     *                  "The value can not be lower than %size%.",
     *                  null,
     *                  array(
     *                      "size" => 5
     *                  ),
     *              ),
     *          ),
     *      ),
     *      array(
     *          array(
     *              "fieldSettings" => array(
     *                  "isMultiple" => false
     *              ),
     *          ),
     *          new CountryValue(
     *              "BE" => array(
     *                  "Name" => "Belgium",
     *                  "Alpha2" => "BE",
     *                  "Alpha3" => "BEL",
     *                  "IDC" => 32,
     *              ),
     *              "FR" => array(
     *                  "Name" => "France",
     *                  "Alpha2" => "FR",
     *                  "Alpha3" => "FRA",
     *                  "IDC" => 33,
     *              ),
     *          )
     *      ),
     *      array(
     *          new ValidationError(
     *              "Field definition does not allow multiple countries to be selected."
     *          ),
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInvalidDataForValidate()
    {
        return [
            'file is too large' => [
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
            ],
            'file is not an image file' => [
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
            ],
            'file is too large and invalid' => [
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
            ],
            'file is an image file but filename ends with .php' => [
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
            ],
            'file is an image file but filename ends with .PHP (upper case)' => [
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
            ],
            'alternative text is null' => [
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
            ],
            'alternative text is empty string' => [
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
            ],
            'Image with not allowed mime type' => [
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
            ],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function provideInputForValuesEqual(): array
    {
        return [
            [
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
            ],
        ];
    }
}
