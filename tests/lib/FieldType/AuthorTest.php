<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Author\Author;
use Ibexa\Core\FieldType\Author\AuthorCollection;
use Ibexa\Core\FieldType\Author\Type as AuthorType;
use Ibexa\Core\FieldType\Author\Value as AuthorValue;
use Ibexa\Core\FieldType\Value;

/**
 * @group fieldType
 * @group ibexa_author
 */
class AuthorTest extends FieldTypeTestCase
{
    /** @var \Ibexa\Core\FieldType\Author\Author[] */
    private $authors;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authors = [
            new Author(['name' => 'Boba Fett', 'email' => 'boba.fett@bountyhunters.com']),
            new Author(['name' => 'Darth Vader', 'email' => 'darth.vader@evilempire.biz']),
            new Author(['name' => 'Luke Skywalker', 'email' => 'luke@imtheone.net']),
        ];
    }

    protected function createFieldTypeUnderTest(): AuthorType
    {
        $fieldType = new AuthorType();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [
            'defaultAuthor' => [
                'type' => 'choice',
                'default' => AuthorType::DEFAULT_VALUE_EMPTY,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): AuthorValue
    {
        return new AuthorValue();
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                'My name',
                InvalidArgumentException::class,
            ],
            [
                23,
                InvalidArgumentException::class,
            ],
            [
                ['foo'],
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                [],
                new AuthorValue([]),
            ],
            [
                [
                    new Author(['name' => 'Boba Fett', 'email' => 'boba.fett@example.com']),
                ],
                new AuthorValue(
                    [
                        new Author(['id' => 1, 'name' => 'Boba Fett', 'email' => 'boba.fett@example.com']),
                    ]
                ),
            ],
            [
                [
                    new Author(['name' => 'Boba Fett', 'email' => 'boba.fett@example.com']),
                    new Author(['name' => 'Darth Vader', 'email' => 'darth.vader@example.com']),
                ],
                new AuthorValue(
                    [
                        new Author(['id' => 1, 'name' => 'Boba Fett', 'email' => 'boba.fett@example.com']),
                        new Author(['id' => 2, 'name' => 'Darth Vader', 'email' => 'darth.vader@example.com']),
                    ]
                ),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new AuthorValue([]),
                [],
            ],
            [
                new AuthorValue(
                    [
                        new Author(['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com']),
                    ]
                ),
                [
                    ['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com'],
                ],
            ],
            [
                new AuthorValue(
                    [
                        new Author(['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com']),
                        new Author(['id' => 2, 'name' => 'Joe Bielefeld', 'email' => 'bielefeld@example.com']),
                    ]
                ),
                [
                    ['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com'],
                    ['id' => 2, 'name' => 'Joe Bielefeld', 'email' => 'bielefeld@example.com'],
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        return [
            [
                [],
                new AuthorValue([]),
            ],
            [
                [
                    ['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com'],
                ],
                new AuthorValue(
                    [
                        new Author(['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com']),
                    ]
                ),
            ],
            [
                [
                    ['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com'],
                    ['id' => 2, 'name' => 'Joe Bielefeld', 'email' => 'bielefeld@example.com'],
                ],
                new AuthorValue(
                    [
                        new Author(['id' => 1, 'name' => 'Joe Sindelfingen', 'email' => 'sindelfingen@example.com']),
                        new Author(['id' => 2, 'name' => 'Joe Bielefeld', 'email' => 'bielefeld@example.com']),
                    ]
                ),
            ],
        ];
    }

    public function provideValidFieldSettings(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'defaultAuthor' => AuthorType::DEFAULT_VALUE_EMPTY,
                ],
            ],
            [
                [
                    'defaultAuthor' => AuthorType::DEFAULT_CURRENT_USER,
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    // non-existent setting
                    'useSeconds' => 23,
                ],
            ],
            [
                [
                    //defaultAuthor must be constant
                    'defaultAuthor' => 42,
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($this->authors);
        parent::tearDown();
    }

    public function testValidatorConfigurationSchema(): void
    {
        $ft = $this->createFieldTypeUnderTest();
        self::assertEmpty(
            $ft->getValidatorConfigurationSchema(),
            'The validator configuration schema does not match what is expected.'
        );
    }

    public function testAcceptValueInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $ft = $this->createFieldTypeUnderTest();
        $ft->acceptValue($this->createMock(Value::class));
    }

    public function testAcceptValueInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $ft = $this->createFieldTypeUnderTest();
        $value = new AuthorValue();
        $value->authors = 'This is not a valid author collection';
        $ft->acceptValue($value);
    }

    public function testAcceptValueValidFormat(): void
    {
        $ft = $this->createFieldTypeUnderTest();
        $author = new Author();
        $author->name = 'Boba Fett';
        $author->email = 'boba.fett@bountyhunters.com';
        $value = new AuthorValue([$author]);
        $newValue = $ft->acceptValue($value);
        self::assertSame($value, $newValue);
    }

    public function testBuildFieldValueWithoutParam(): void
    {
        $value = new AuthorValue();
        self::assertInstanceOf(AuthorCollection::class, $value->authors);
        self::assertSame([], $value->authors->getArrayCopy());
    }

    public function testBuildFieldValueWithParam(): void
    {
        $value = new AuthorValue($this->authors);
        self::assertInstanceOf(AuthorCollection::class, $value->authors);
        self::assertSame($this->authors, $value->authors->getArrayCopy());
    }

    public function testFieldValueToString(): void
    {
        $value = new AuthorValue($this->authors);

        $authorsName = [];
        foreach ($this->authors as $author) {
            $authorsName[] = $author->name;
        }

        self::assertSame(implode(', ', $authorsName), $value->__toString());
    }

    public function testAddAuthor(): void
    {
        $value = new AuthorValue();
        $value->authors[] = $this->authors[0];
        self::assertSame(1, $this->authors[0]->id);
        self::assertCount(1, $value->authors);

        $this->authors[1]->id = 10;
        $value->authors[] = $this->authors[1];
        self::assertSame(10, $this->authors[1]->id);

        $this->authors[2]->id = -1;
        $value->authors[] = $this->authors[2];
        self::assertSame($this->authors[1]->id + 1, $this->authors[2]->id);
        self::assertCount(3, $value->authors);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Author\AuthorCollection::removeAuthorsById
     */
    public function testRemoveAuthors(): void
    {
        $existingIds = [];
        foreach ($this->authors as $author) {
            $id = random_int(1, 100);
            if (in_array($id, $existingIds)) {
                continue;
            }
            $author->id = $id;
            $existingIds[] = $id;
        }

        $value = new AuthorValue($this->authors);
        $value->authors->removeAuthorsById([$this->authors[1]->id, $this->authors[2]->id]);
        self::assertSame(count($this->authors) - 2, count($value->authors));
        self::assertSame([$this->authors[0]], $value->authors->getArrayCopy());
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_author';
    }

    public function provideDataForGetName(): array
    {
        $authorList = new AuthorValue(
            [
                new Author(['id' => 1, 'name' => 'Boba Fett', 'email' => 'boba.fett@example.com']),
                new Author(['id' => 2, 'name' => 'Luke Skywalker', 'email' => 'luke.skywalker@example.com']),
            ]
        );

        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [$authorList, 'Boba Fett', [], 'en_GB'],
        ];
    }
}
