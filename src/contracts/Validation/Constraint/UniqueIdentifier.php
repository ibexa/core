<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Validation\Constraint;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Validator\Constraint;

abstract class UniqueIdentifier extends Constraint implements TranslationContainerInterface
{
    protected const MESSAGE = 'ibexa.identifier_already_exists';

    public string $message = self::MESSAGE;

    public ?string $existingIdPath = null;

    public string $identifierPath;

    public ?string $reportErrorPath = null;

    public function getDefaultOption(): string
    {
        return 'identifierPath';
    }

    /**
     * @return array<self::*>
     */
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }

    public function getRequiredOptions(): array
    {
        return ['identifierPath'];
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create(static::MESSAGE, 'validators')
                ->setDesc(static::getAlreadyExistsMessageDesc()),
        ];
    }

    protected static function getAlreadyExistsMessageDesc(): string
    {
        return 'Identifier already exists.';
    }
}
