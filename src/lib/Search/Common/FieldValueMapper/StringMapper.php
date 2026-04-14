<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType;
use Ibexa\Core\Search\Common\FieldValueMapper;
use Psr\Log\LoggerInterface;

/**
 * Common string field value mapper implementation.
 */
class StringMapper extends FieldValueMapper
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public const REPLACE_WITH_SPACE_PATTERN = '([\x09\x0B\x0C]+)';
    public const REMOVE_PATTERN = '([\x00-\x08\x0E-\x1F]+)';
    public const MAX_TERM_LENGTH = 32766;

    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof FieldType\StringField;
    }

    public function map(Field $field)
    {
        return $this->convert($field->getValue());
    }

    /**
     * Convert to a proper search engine representation.
     *
     * @param mixed $value
     */
    protected function convert($value): string
    {
        // Replace tab, vertical tab, form-feed chars to single space.
        $value = preg_replace(
            self::REPLACE_WITH_SPACE_PATTERN,
            ' ',
            (string)$value
        );

        // Remove non-printable characters.
        $value = preg_replace(
            self::REMOVE_PATTERN,
            '',
            (string)$value
        );

        // Enforce Lucene's bytes MAX_TERM_LENGTH to avoid silent indexing failures
        $value = (string)$value;
        $truncated = mb_strcut($value, 0, self::MAX_TERM_LENGTH);

        if (strlen($truncated) < strlen($value)) {
            $this->logger->warning(
                sprintf(
                    'String field value was truncated from %d to %d bytes (max term length: %d).',
                    strlen($value),
                    strlen($truncated),
                    self::MAX_TERM_LENGTH
                )
            );
        }

        return $truncated;
    }
}

class_alias(StringMapper::class, 'eZ\Publish\Core\Search\Common\FieldValueMapper\StringMapper');
