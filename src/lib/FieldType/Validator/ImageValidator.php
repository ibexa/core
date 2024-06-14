<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Validator;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value;

class ImageValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function validateConstraints($constraints, ?FieldDefinition $fieldDefinition = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Value $value, ?FieldDefinition $fieldDefinition = null): bool
    {
        $mimeTypes = [];
        if (null !== $fieldDefinition) {
            $mimeTypes = $fieldDefinition->getFieldSettings()['mimeTypes'] ?? [];
        }

        $isValid = true;
        if (isset($value->inputUri) && !$this->innerValidate($value->inputUri, $mimeTypes)) {
            $isValid = false;
        }

        // BC: Check if file is a valid image if the value of 'id' matches a local file
        if (isset($value->id) && file_exists($value->id) && !$this->innerValidate($value->id, $mimeTypes)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array<string> $mimeTypes
     */
    private function innerValidate($filePath, array $mimeTypes): bool
    {
        // silence `getimagesize` error as extension-wise valid image files might produce it anyway
        // note that file extension checking is done using other validation which should be called before this one
        $imageData = @getimagesize($filePath);
        if (false === $imageData) {
            $this->errors[] = new ValidationError(
                'A valid image file is required.',
                null,
                [],
                'id'
            );

            return false;
        }

        // If array with mime types is empty, it means that all mime types are allowed
        if (empty($mimeTypes)) {
            return true;
        }

        $mimeType = $imageData['mime'];
        if (!in_array($mimeType, $mimeTypes, true)) {
            $this->errors[] = new ValidationError(
                'The mime type of the file is invalid (%mimeType%). Allowed mime types are %mimeTypes%.',
                null,
                [
                    '%mimeType%' => $mimeType,
                    '%mimeTypes%' => implode(', ', $mimeTypes),
                ],
                'id'
            );

            return false;
        }

        return true;
    }
}
