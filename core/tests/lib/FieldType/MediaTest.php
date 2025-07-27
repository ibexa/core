<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use Ibexa\Core\FieldType\Media\Type as MediaType;
use Ibexa\Core\FieldType\Media\Value as MediaValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_binaryfile
 */
class MediaTest extends BinaryBaseTestCase
{
    protected function createFieldTypeUnderTest(): MediaType
    {
        $fieldType = new MediaType([$this->getBlackListValidator()]);
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getEmptyValueExpectation(): MediaValue
    {
        return new MediaValue();
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [
            'mediaType' => [
                'type' => 'choice',
                'default' => MediaType::TYPE_HTML5_VIDEO,
            ],
        ];
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        yield from parent::provideInvalidInputForAcceptValue();

        yield [
            new MediaValue(['id' => '/foo/bar']),
            InvalidArgumentException::class,
        ];
        yield [
            new MediaValue(['hasController' => 'yes']),
            InvalidArgumentException::class,
        ];
        yield [
            new MediaValue(['autoplay' => 'yes']),
            InvalidArgumentException::class,
        ];
        yield [
            new MediaValue(['loop' => 'yes']),
            InvalidArgumentException::class,
        ];
        yield [
            new MediaValue(['height' => []]),
            InvalidArgumentException::class,
        ];
        yield [
            new MediaValue(['width' => []]),
            InvalidArgumentException::class,
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new MediaValue(),
        ];

        yield 'empty MediaValue object' => [
            new MediaValue(),
            new MediaValue(),
        ];

        yield 'file path string' => [
            __FILE__,
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri' => [
            ['inputUri' => __FILE__],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and fileSize' => [
            [
                'inputUri' => __FILE__,
                'fileSize' => 23,
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => 23,
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and mimeType' => [
            [
                'inputUri' => __FILE__,
                'mimeType' => 'application/text+php',
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'application/text+php',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and hasController' => [
            [
                'inputUri' => __FILE__,
                'hasController' => true,
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => true,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and autoplay' => [
            [
                'inputUri' => __FILE__,
                'autoplay' => true,
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => true,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and loop' => [
            [
                'inputUri' => __FILE__,
                'loop' => true,
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and width' => [
            [
                'inputUri' => __FILE__,
                'width' => 23,
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 23,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];

        yield 'array with inputUri and height' => [
            [
                'inputUri' => __FILE__,
                'height' => 42,
            ],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 42,
                    'uri' => '',
                ]
            ),
        ];

        // BC with 5.2 (EZP-22808). Id can be used as input instead of inputUri.
        yield 'BC: array with id' => [
            ['id' => __FILE__],
            new MediaValue(
                [
                    'inputUri' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => false,
                    'width' => 0,
                    'height' => 0,
                    'uri' => '',
                ]
            ),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new MediaValue(),
                null,
            ],
            [
                new MediaValue(
                    [
                        'inputUri' => __FILE__,
                        'fileName' => basename(__FILE__),
                        'fileSize' => filesize(__FILE__),
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                        'uri' => 'http://' . basename(__FILE__),
                    ]
                ),
                [
                    'id' => null,
                    'inputUri' => __FILE__,
                    'path' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                    'uri' => 'http://' . basename(__FILE__),
                ],
            ],
            // BC with 5.0 (EZP-20948). Path can be used as input instead of inputUri.
            [
                new MediaValue(
                    [
                        'path' => __FILE__,
                        'fileName' => basename(__FILE__),
                        'fileSize' => filesize(__FILE__),
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                        'uri' => 'http://' . basename(__FILE__),
                    ]
                ),
                [
                    'id' => null,
                    'inputUri' => __FILE__,
                    'path' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                    'uri' => 'http://' . basename(__FILE__),
                ],
            ],
            // BC with 5.2 (EZP-22808). Id can be used as input instead of inputUri.
            [
                new MediaValue(
                    [
                        'id' => __FILE__,
                        'fileName' => basename(__FILE__),
                        'fileSize' => filesize(__FILE__),
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                        'uri' => 'http://' . basename(__FILE__),
                    ]
                ),
                [
                    'id' => null,
                    'inputUri' => __FILE__,
                    'path' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                    'uri' => 'http://' . basename(__FILE__),
                ],
            ],
            // BC with 5.2 (EZP-22808). Id is recognized as such if not pointing to existing file.
            [
                new MediaValue(
                    [
                        'id' => 'application/asdf1234.pdf',
                        'fileName' => 'asdf1234.pdf',
                        'fileSize' => 12345,
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                        'uri' => 'http://asdf1234.pdf',
                    ]
                ),
                [
                    'id' => 'application/asdf1234.pdf',
                    'inputUri' => null,
                    'path' => null,
                    'fileName' => 'asdf1234.pdf',
                    'fileSize' => 12345,
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                    'uri' => 'http://asdf1234.pdf',
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new MediaValue(),
            ],
            [
                [
                    'id' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                ],
                new MediaValue(
                    [
                        'id' => __FILE__,
                        'fileName' => basename(__FILE__),
                        'fileSize' => filesize(__FILE__),
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                    ]
                ),
            ],
            // BC with 5.0 (EZP-20948). Path can be used as input instead of ID.
            [
                [
                    'path' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                ],
                new MediaValue(
                    [
                        'id' => __FILE__,
                        'fileName' => basename(__FILE__),
                        'fileSize' => filesize(__FILE__),
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                    ]
                ),
            ],
            // BC with 5.2 (EZP-22808). Id can be used as input instead of inputUri.
            [
                [
                    'id' => __FILE__,
                    'fileName' => basename(__FILE__),
                    'fileSize' => filesize(__FILE__),
                    'mimeType' => 'text/plain',
                    'hasController' => false,
                    'autoplay' => false,
                    'loop' => true,
                    'width' => 0,
                    'height' => 0,
                ],
                new MediaValue(
                    [
                        'id' => null,
                        'inputUri' => __FILE__,
                        'path' => __FILE__,
                        'fileName' => basename(__FILE__),
                        'fileSize' => filesize(__FILE__),
                        'mimeType' => 'text/plain',
                        'hasController' => false,
                        'autoplay' => false,
                        'loop' => true,
                        'width' => 0,
                        'height' => 0,
                    ]
                ),
            ],
            // @todo: Test for REST upload hash
        ];
    }

    public function provideValidFieldSettings(): iterable
    {
        return [
            [
                [],
            ],
            [
                [
                    'mediaType' => MediaType::TYPE_FLASH,
                ],
            ],
            [
                [
                    'mediaType' => MediaType::TYPE_REALPLAYER,
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    'not-existing' => 23,
                ],
            ],
            [
                // mediaType must be constant
                [
                    'mediaType' => 23,
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_media';
    }

    public function provideDataForGetName(): array
    {
        return [
            [
                new MediaValue(),
                '',
                [],
                'en_GB',
            ],
            [
                new MediaValue(['fileName' => 'sindelfingen.jpg']),
                'sindelfingen.jpg',
                [],
                'en_GB',
            ],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'valid media file within size limit' => [
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
                    'fileName' => 'sindelfingen.mp4',
                    'fileSize' => 15000,
                    'downloadCount' => 0,
                    'mimeType' => 'video/mp4',
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
            new MediaValue(
                [
                    'id' => 'some/file/here',
                    'fileName' => 'sindelfingen.mp4',
                    'fileSize' => 150000,
                    'mimeType' => 'video/mp4',
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

        yield 'disallowed file extension' => [
            [
                'validatorConfiguration' => [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1,
                    ],
                ],
            ],
            new MediaValue(
                [
                    'id' => 'phppng.php',
                    'fileName' => 'phppng.php',
                    'fileSize' => 0.01,
                    'mimeType' => 'video/mp4',
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
}
