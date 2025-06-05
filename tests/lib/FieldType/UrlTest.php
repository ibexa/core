<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Url\Type as UrlType;
use Ibexa\Core\FieldType\Url\Value as UrlValue;

/**
 * @group fieldType
 * @group ibexa_url
 */
class UrlTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): UrlType
    {
        $fieldType = new UrlType();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): UrlValue
    {
        return new UrlValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
            [
                new UrlValue(23),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new UrlValue(),
        ];

        yield 'url string' => [
            'http://example.com/sindelfingen',
            new UrlValue('http://example.com/sindelfingen'),
        ];

        yield 'UrlValue object' => [
            new UrlValue('http://example.com/sindelfingen', 'Sindelfingen!'),
            new UrlValue('http://example.com/sindelfingen', 'Sindelfingen!'),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new UrlValue(),
                null,
            ],
            [
                new UrlValue('http://example.com/sindelfingen'),
                [
                    'link' => 'http://example.com/sindelfingen',
                    'text' => '',
                ],
            ],
            [
                new UrlValue('http://example.com/sindelfingen', 'Sindelfingen!'),
                [
                    'link' => 'http://example.com/sindelfingen',
                    'text' => 'Sindelfingen!',
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new UrlValue(),
            ],
            [
                [
                    'link' => 'http://example.com/sindelfingen',
                    'text' => null,
                ],
                new UrlValue('http://example.com/sindelfingen'),
            ],
            [
                [
                    'link' => 'http://example.com/sindelfingen',
                    'text' => 'Sindelfingen!',
                ],
                new UrlValue('http://example.com/sindelfingen', 'Sindelfingen!'),
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_url';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new UrlValue('', 'Url text'), 'Url text', [], 'en_GB'],
        ];
    }
}
