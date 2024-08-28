<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Validator;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value as BaseValue;

class FileExtensionBlackListValidator extends Validator
{
    protected $constraints = [
        'extensionsBlackList' => [],
    ];

    protected $constraintsSchema = [
        'extensionsBlackList' => [
            'type' => 'array',
            'default' => [],
        ],
    ];

    /**
     * @param \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface $configResolver
     */
    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->constraints['extensionsBlackList'] = $configResolver->getParameter(
            'io.file_storage.file_type_blacklist'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateConstraints($constraints)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(BaseValue $value, ?FieldDefinition $fieldDefinition = null): bool
    {
        $this->errors = [];

        $this->validateFileExtension($value->fileName);

        return empty($this->errors);
    }

    public function validateFileExtension(string $fileName): void
    {
        if (
            pathinfo($fileName, PATHINFO_BASENAME) !== $fileName
            || in_array(
                strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                $this->constraints['extensionsBlackList'],
                true
            )
        ) {
            $this->errors[] = new ValidationError(
                'A valid file is required. The following file extensions are not allowed: %extensionsBlackList%',
                null,
                [
                    '%extensionsBlackList%' => implode(', ', $this->constraints['extensionsBlackList']),
                ],
                'fileExtensionBlackList'
            );
        }
    }

    /**
     * @return array<\Ibexa\Contracts\Core\FieldType\ValidationError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
