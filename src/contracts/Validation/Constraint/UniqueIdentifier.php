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

abstract class UniqueIdentifier extends Constraint implements TranslationContainerInterface
{
    protected const string MESSAGE = 'ibexa.identifier_already_exists';

    public string $message = self::MESSAGE;

    public ?string $existingIdPath = null;

    public string $identifierPath;

    public ?string $reportErrorPath = null;

    /**
     * @param string|array<string, mixed> $identifierPath Property path of the identifier, or (deprecated) an options array
     * @param array<string>|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string|array $identifierPath,
        ?string $existingIdPath = null,
        ?string $reportErrorPath = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        if (is_array($identifierPath)) {
            trigger_deprecation(
                'ibexa/core',
                '6.0',
                'Passing an options array to "%s" is deprecated, use named arguments instead.',
                static::class
            );

            $options = $identifierPath;
            $identifierPath = $options['identifierPath'] ?? $options['value'] ?? null;
            $existingIdPath ??= $options['existingIdPath'] ?? null;
            $reportErrorPath ??= $options['reportErrorPath'] ?? null;
            $message ??= $options['message'] ?? null;
            $groups ??= $options['groups'] ?? null;
            $payload ??= $options['payload'] ?? null;

            if (!is_string($identifierPath)) {
                throw new \InvalidArgumentException(sprintf('The "identifierPath" option of "%s" is required.', static::class));
            }
        }

        parent::__construct(null, $groups, $payload);

        $this->identifierPath = $identifierPath;
        $this->existingIdPath = $existingIdPath;
        $this->reportErrorPath = $reportErrorPath;
        $this->message = $message ?? static::MESSAGE;
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
