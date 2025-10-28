<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException as APIContentFieldValidationException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;

/**
 * This Exception is thrown on create or update content one or more given fields are not valid.
 */
class ContentFieldValidationException extends APIContentFieldValidationException implements Translatable
{
    use TranslatableBase;

    private const MAX_MESSAGES_NUMBER = 32;

    /**
     * Contains an array of field ValidationError objects indexed with FieldDefinition id and language code.
     *
     * Example:
     * <code>
     *  $fieldErrors = $exception->getFieldErrors();
     *  $fieldErrors[43]["eng-GB"]->getTranslatableMessage();
     * </code>
     *
     * @var array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError|\Ibexa\Contracts\Core\FieldType\ValidationError[]>>
     */
    protected $errors;

    /** @var string|null */
    protected $contentName;

    /**
     * Generates: Content fields did not validate.
     *
     * Also sets the given $fieldErrors to the internal property, retrievable by getFieldErrors()
     *
     * @param array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError|\Ibexa\Contracts\Core\FieldType\ValidationError[]>> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $this->setMessageTemplate('Content fields did not validate');
        parent::__construct($this->getBaseTranslation());
    }

    /**
     * Generates: Content fields did not validate exception with additional information on affected fields.
     *
     * @param array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError|\Ibexa\Contracts\Core\FieldType\ValidationError[]>> $errors
     */
    public static function createNewWithMultiline(array $errors, ?string $contentName = null): self
    {
        $exception = new self($errors);
        $exception->contentName = $contentName;

        $exception->setMessageTemplate('Content%contentName%fields did not validate: %errors%');
        $exception->setParameters([
            '%errors%' => $exception->generateValidationErrorsMessages(),
            '%contentName%' => $exception->contentName !== null ? ' "'. $exception->contentName. '" ' : ' ',
        ]);
        $exception->message = $exception->getBaseTranslation();

        return $exception;
    }

    /**
     * Returns an array of field validation error messages.
     *
     * @return array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError|\Ibexa\Contracts\Core\FieldType\ValidationError[]>>
     */
    public function getFieldErrors()
    {
        return $this->errors;
    }

    private function generateValidationErrorsMessages(): string
    {
        $validationErrors = $this->collectValidationErrors();
        $maxMessagesNumber = self::MAX_MESSAGES_NUMBER;

        if (count($validationErrors) > $maxMessagesNumber) {
            array_splice($validationErrors, $maxMessagesNumber);
            $validationErrors[] = sprintf('Limit: %d of validation errors has been exceeded.', $maxMessagesNumber);
        }

        /** @var callable(string|\Ibexa\Contracts\Core\Repository\Values\Translation): string $convertToString */
        $convertToString = static function ($error): string {
            return (string)$error;
        };
        $validationErrors = array_map($convertToString, $validationErrors);

        return "\n- " . implode("\n- ", $validationErrors);
    }

    /**
     * @return array<\Ibexa\Contracts\Core\Repository\Values\Translation>
     */
    private function collectValidationErrors(): array
    {
        $messages = [];
        foreach ($this->getFieldErrors() as $validationErrors) {
            foreach ($validationErrors as $validationError) {
                if (is_array($validationError)) {
                    foreach ($validationError as $item) {
                        $messages[] = $item->getTranslatableMessage();
                    }
                } else {
                    $messages[] = $validationError->getTranslatableMessage();
                }
            }
        }

        return $messages;
    }
}

class_alias(ContentFieldValidationException::class, 'eZ\Publish\Core\Base\Exceptions\ContentFieldValidationException');
