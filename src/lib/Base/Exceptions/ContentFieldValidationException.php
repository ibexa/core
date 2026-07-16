<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException as APIContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Values\Translation;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;
use Ibexa\Core\FieldType\ValidationError;

/**
 * This Exception is thrown on create or update content one or more given fields are not valid.
 */
class ContentFieldValidationException extends APIContentFieldValidationException implements Translatable
{
    use TranslatableBase;

    private const int MAX_MESSAGES_NUMBER = 32;

    /**
     * Contains an array of field ValidationError objects indexed with FieldDefinition id and language code.
     *
     * Example:
     * ```
     *  $fieldErrors = $exception->getFieldErrors();
     *  $fieldErrors[43]["eng-GB"][0]->getTranslatableMessage();
     * ```
     *
     * @var array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError[]>>
     */
    protected array $errors;

    protected ?string $contentName;

    /**
     * Generates: Content fields did not validate.
     *
     * Also sets the given $fieldErrors to the internal property, retrievable by getFieldErrors()
     *
     * @param array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError[]>> $errors
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
     * @param array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError[]>> $errors
     */
    public static function createNewWithMultiline(
        array $errors,
        ?string $contentName = null
    ): self {
        $exception = new self($errors);
        $exception->contentName = $contentName;

        $exception->setMessageTemplate('Content "%contentName%" fields did not validate: %errors%');
        $exception->setParameters([
            '%errors%' => $exception->generateValidationErrorsMessages(),
            '%contentName%' => $exception->contentName ?? '',
        ]);
        $exception->message = $exception->getBaseTranslation();

        return $exception;
    }

    /**
     * Returns an array of field validation error messages.
     *
     * @return array<int, array<string, \Ibexa\Contracts\Core\FieldType\ValidationError[]>>
     */
    public function getFieldErrors(): array
    {
        return $this->errors;
    }

    private function generateValidationErrorsMessages(): string
    {
        $validationErrors = $this->collectValidationErrors();
        if (count($validationErrors) > self::MAX_MESSAGES_NUMBER) {
            array_splice($validationErrors, self::MAX_MESSAGES_NUMBER);
            $validationErrors[] = new Translation\Message(
                'Limit of %max_message_number% validation errors has been exceeded.',
                [
                    '%max_message_number%' => self::MAX_MESSAGES_NUMBER,
                ]
            );
        }

        $validationErrors = array_map(
            static fn (Translation $error): string => (string)$error,
            $validationErrors
        );

        return "\n- " . implode("\n- ", $validationErrors);
    }

    /**
     * @return array<Translation>
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
