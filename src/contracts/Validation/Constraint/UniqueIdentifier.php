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
     * @param string $identifierPath Property path of the identifier to check for uniqueness
     * @param string|null $existingIdPath Property path of the ID of the object being updated, so it does not collide with itself
     * @param string|null $reportErrorPath Property path to report the violation on (defaults to $identifierPath)
     * @param array<string>|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $identifierPath,
        ?string $existingIdPath = null,
        ?string $reportErrorPath = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
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
