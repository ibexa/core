<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Media;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BinaryBase\Type as BaseType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The TextLine field type.
 *
 * This field type represents a simple string.
 */
class Type extends BaseType implements TranslationContainerInterface
{
    /**
     * List of possible media type settings.
     */
    public const string TYPE_FLASH = 'flash';
    public const string TYPE_QUICKTIME = 'quick_time';
    public const string TYPE_REALPLAYER = 'real_player';
    public const string TYPE_SILVERLIGHT = 'silverlight';
    public const string TYPE_WINDOWSMEDIA = 'windows_media_player';
    public const string TYPE_HTML5_VIDEO = 'html5_video';
    public const string TYPE_HTML5_AUDIO = 'html5_audio';

    /**
     * Type constants for validation.
     */
    private static array $availableTypes = [
        self::TYPE_FLASH,
        self::TYPE_QUICKTIME,
        self::TYPE_REALPLAYER,
        self::TYPE_SILVERLIGHT,
        self::TYPE_WINDOWSMEDIA,
        self::TYPE_HTML5_VIDEO,
        self::TYPE_HTML5_AUDIO,
    ];

    /** @var array */
    protected array $settingsSchema = [
        'mediaType' => [
            'type' => 'choice',
            'default' => self::TYPE_HTML5_VIDEO,
        ],
    ];

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_media';
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    public function validateFieldSettings(array $fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $name => $value) {
            if (isset($this->settingsSchema[$name])) {
                if ($name === 'mediaType' && !in_array($value, self::$availableTypes, true)) {
                    $validationErrors[] = new ValidationError(
                        "Setting '%setting%' is of unknown type",
                        null,
                        [
                            '%setting%' => $name,
                        ],
                        "[$name]"
                    );
                }
            } else {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' is unknown",
                    null,
                    [
                        '%setting%' => $name,
                    ],
                    "[$name]"
                );
            }
        }

        return $validationErrors;
    }

    /**
     * Creates a specific value of the derived class from $inputValue.
     *
     * @param array $inputValue
     *
     * @return \Ibexa\Core\FieldType\Media\Value
     */
    protected function createValue(array $inputValue)
    {
        $inputValue = $this->regenerateUri($inputValue);

        return new Value($inputValue);
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Media\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        parent::checkValueStructure($value);

        if (!is_bool($value->hasController)) {
            throw new InvalidArgumentType(
                '$value->hasController',
                'bool',
                $value->hasController
            );
        }
        if (!is_bool($value->autoplay)) {
            throw new InvalidArgumentType(
                '$value->autoplay',
                'bool',
                $value->autoplay
            );
        }
        if (!is_bool($value->loop)) {
            throw new InvalidArgumentType(
                '$value->loop',
                'bool',
                $value->loop
            );
        }

        if (!is_int($value->height)) {
            throw new InvalidArgumentType(
                '$value->height',
                'int',
                $value->height
            );
        }
        if (!is_int($value->width)) {
            throw new InvalidArgumentType(
                '$value->width',
                'int',
                $value->width
            );
        }
    }

    /**
     * Attempts to complete the data in $value.
     *
     * @param \Ibexa\Core\FieldType\Media\Value|\Ibexa\Core\FieldType\Value $value
     */
    protected function completeValue(BaseValue $value)
    {
        parent::completeValue($value);

        if (isset($value->hasController) && $value->hasController === null) {
            $value->hasController = false;
        }
        if (isset($value->autoplay) && $value->autoplay === null) {
            $value->autoplay = false;
        }
        if (isset($value->loop) && $value->loop === null) {
            $value->loop = false;
        }

        if (isset($value->height) && $value->height === null) {
            $value->height = 0;
        }
        if (isset($value->width) && $value->width === null) {
            $value->width = 0;
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\Media\Value $value
     *
     * @return array<string, mixed>|null
     */
    public function toHash(SPIValue $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        $hash = parent::toHash($value);

        $hash['hasController'] = $value->hasController;
        $hash['autoplay'] = $value->autoplay;
        $hash['loop'] = $value->loop;
        $hash['width'] = $value->width;
        $hash['height'] = $value->height;

        return $hash;
    }

    public function fromPersistenceValue(FieldValue $fieldValue): Value
    {
        if ($fieldValue->externalData === null) {
            return $this->getEmptyValue();
        }

        /** @var \Ibexa\Core\FieldType\Media\Value $result */
        $result = parent::fromPersistenceValue($fieldValue);

        $result->hasController = $fieldValue->externalData['hasController'] ?? false;
        $result->autoplay = $fieldValue->externalData['autoplay'] ?? false;
        $result->loop = $fieldValue->externalData['loop'] ?? false;
        $result->height = $fieldValue->externalData['height'] ?? 0;
        $result->width = $fieldValue->externalData['width'] ?? 0;

        return $result;
    }

    public function isSearchable(): bool
    {
        return false;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_media.name', 'ibexa_fieldtypes')->setDesc('Media'),
        ];
    }
}
