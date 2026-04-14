<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\StringField;
use Ibexa\Core\Search\Common\FieldValueMapper\StringMapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ibexa\Core\Search\Common\FieldValueMapper\StringMapper
 */
final class StringMapperTest extends TestCase
{
    private StringMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new StringMapper(
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testCanMap(): void
    {
        $field = $this->createFieldWithValue('hello', new StringField());

        self::assertTrue($this->mapper->canMap($field));
    }

    public function testMapsPlainString(): void
    {
        $field = $this->createFieldWithValue('hello world', new StringField());

        self::assertSame('hello world', $this->mapper->map($field));
    }

    public function testStripsNonPrintableCharacters(): void
    {
        $field = $this->createFieldWithValue("hello\x01\x02world", new StringField());

        self::assertSame('helloworld', $this->mapper->map($field));
    }

    public function testReplacesTabAndVerticalWhitespaceWithSpace(): void
    {
        $field = $this->createFieldWithValue("hello\x09world\x0Bfoo", new StringField());

        self::assertSame('hello world foo', $this->mapper->map($field));
    }

    public function testTruncatesToMaxTermLength(): void
    {
        $longValue = str_repeat('a', StringMapper::MAX_TERM_LENGTH + 100);
        $field = $this->createFieldWithValue($longValue, new StringField());
        $result = $this->mapper->map($field);

        self::assertSame(StringMapper::MAX_TERM_LENGTH, strlen($result));
    }

    public function testTruncatesMultibyteStringAtCharacterBoundary(): void
    {
        // Each UTF-8 character here is 3 bytes (€ = E2 82 AC).
        // Fill up to just past the limit so truncation must happen on a char boundary.
        $char = '€';
        $charBytes = strlen($char);

        self::assertSame(3, $charBytes);

        $count = (int) ceil((StringMapper::MAX_TERM_LENGTH + $charBytes) / $charBytes);
        $longValue = str_repeat($char, $count);

        $field = $this->createFieldWithValue($longValue, new StringField());
        $result = $this->mapper->map($field);

        self::assertSame(StringMapper::MAX_TERM_LENGTH, strlen($result));
        // Result must be valid UTF-8 (no split mid-character).
        self::assertSame(1, preg_match('//u', $result));
    }

    public function testValueWithinLimitIsNotTruncated(): void
    {
        $value = str_repeat('a', StringMapper::MAX_TERM_LENGTH);
        $field = $this->createFieldWithValue($value, new StringField());

        self::assertSame($value, $this->mapper->map($field));
    }

    private function createFieldWithValue(string $value, StringField $type): Field
    {
        $field = $this->createMock(Field::class);
        $field
            ->method('getValue')
            ->willReturn($value);
        $field
            ->method('getType')
            ->willReturn($type);

        return $field;
    }
}
