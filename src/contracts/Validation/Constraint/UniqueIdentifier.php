<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Validation\Constraint;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

abstract class UniqueIdentifier extends Constraint implements TranslationContainerInterface
{
    private const array OPTION_NAMES = [
        'identifierPath',
        'value',
        'existingIdPath',
        'reportErrorPath',
        'message',
        'groups',
        'payload',
    ];

    protected const string MESSAGE = 'ibexa.identifier_already_exists';

    public string $message = self::MESSAGE;

    public ?string $existingIdPath = null;

    public string $identifierPath;

    public ?string $reportErrorPath = null;

    /**
     * @param string|array<string, mixed>|null $identifierPath Property path of the identifier, or (deprecated) an options array
     * @param string|array<string>|null $existingIdPath Property path of the existing identifier, or validation groups when using the legacy positional signature
     * @param mixed $reportErrorPath Property path to report validation errors on, or the payload when using the legacy positional signature
     * @param array<string>|null $groups
     * @param array<string, mixed>|null $options Deprecated options array
     */
    #[HasNamedArguments]
    public function __construct(
        string|array|null $identifierPath = null,
        string|array|null $existingIdPath = null,
        mixed $reportErrorPath = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        ?array $options = null
    ) {
        // Preserve Constraint's former ($options, $groups, $payload) positional signature.
        if (is_array($existingIdPath)) {
            $payload ??= $reportErrorPath;
            $groups ??= $existingIdPath;
            $existingIdPath = null;
            $reportErrorPath = null;
        }

        if (is_array($identifierPath)) {
            $options = array_merge($identifierPath, $options ?? []);
            $identifierPath = null;
        }

        if ($options !== null) {
            trigger_deprecation(
                'ibexa/core',
                '6.0',
                'Passing an options array to "%s" is deprecated, use named arguments instead.',
                static::class
            );

            $this->validateOptionNames($options);
            if (array_key_exists('value', $options)) {
                $options['identifierPath'] = $options['value'];
            }

            $identifierPath ??= $options['identifierPath'] ?? null;
            $existingIdPath ??= $options['existingIdPath'] ?? null;
            $reportErrorPath ??= $options['reportErrorPath'] ?? null;
            $message ??= $options['message'] ?? null;
            $groups ??= $options['groups'] ?? null;
            $payload ??= $options['payload'] ?? null;
        }

        if ($identifierPath === null) {
            throw new MissingOptionsException(
                sprintf('The option "identifierPath" must be set for constraint "%s".', static::class),
                ['identifierPath']
            );
        }
        if (!is_string($identifierPath)) {
            throw new \TypeError(sprintf('The "identifierPath" option of "%s" must be a string.', static::class));
        }
        if (!is_string($existingIdPath) && $existingIdPath !== null) {
            throw new \TypeError(sprintf('The "existingIdPath" option of "%s" must be a string or null.', static::class));
        }
        if (!is_string($reportErrorPath) && $reportErrorPath !== null) {
            throw new \TypeError(sprintf('The "reportErrorPath" option of "%s" must be a string or null.', static::class));
        }

        parent::__construct(null, $groups, $payload);

        $this->identifierPath = $identifierPath;
        $this->existingIdPath = $existingIdPath;
        $this->reportErrorPath = $reportErrorPath;
        $this->message = $message ?? static::MESSAGE;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function validateOptionNames(array $options): void
    {
        $invalidOptions = array_diff(array_keys($options), self::OPTION_NAMES);
        if ($invalidOptions === []) {
            return;
        }

        throw new InvalidOptionsException(
            sprintf(
                'The options "%s" do not exist in constraint "%s".',
                implode('", "', $invalidOptions),
                static::class
            ),
            $invalidOptions
        );
    }

    /**
     * @return array<self::*>
     */
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create(static::MESSAGE, 'validators')
                ->setDesc(static::getMessageDesc()),
        ];
    }

    protected static function getMessageDesc(): string
    {
        return 'Identifier already exists.';
    }
}
