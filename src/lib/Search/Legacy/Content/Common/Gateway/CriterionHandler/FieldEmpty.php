<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry as Registry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;

/**
 * Field criterion handler.
 */
class FieldEmpty extends FieldBase
{
    /**
     * Field converter registry.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry
     */
    protected $fieldConverterRegistry;

    /**
     * @var \Ibexa\Contracts\Core\Repository\FieldTypeService
     */
    protected $fieldTypeService;

    public function __construct(
        Connection $connection,
        ContentTypeHandler $contentTypeHandler,
        LanguageHandler $languageHandler,
        Registry $fieldConverterRegistry,
        FieldTypeService $fieldTypeService
    ) {
        parent::__construct($connection, $contentTypeHandler, $languageHandler);

        $this->fieldConverterRegistry = $fieldConverterRegistry;
        $this->fieldTypeService = $fieldTypeService;
    }

    /**
     * Check if this criterion handler accepts to handle the given criterion.
     */
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\IsFieldEmpty;
    }

    /**
     * Returns relevant field information for the specified field.
     *
     * Returns an array of the attribute,
     * identifier and the sort column, which should be used.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If no searchable fields are found for the given $fieldIdentifier.
     * @throws \RuntimeException if no converter is found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    protected function getFieldsInformation(?string $fieldIdentifier): array
    {
        if ($fieldIdentifier === null) {
            throw new InvalidArgumentException(
                '$criterion->target',
                sprintf('Criterion target must be set for %s criterion.', Criterion\IsFieldEmpty::class)
            );
        }

        $fieldMapArray = [];
        $fieldMap = $this->contentTypeHandler->getSearchableFieldMap();

        foreach ($fieldMap as $fieldIdentifierMap) {
            if (!isset($fieldIdentifierMap[$fieldIdentifier])) {
                continue;
            }

            $fieldTypeIdentifier = $fieldIdentifierMap[$fieldIdentifier]['field_type_identifier'];
            $fieldMapArray[$fieldTypeIdentifier]['ids'][] = $fieldIdentifierMap[$fieldIdentifier]['field_definition_id'];
            if (!isset($fieldMapArray[$fieldTypeIdentifier]['column'])) {
                $fieldMapArray[$fieldTypeIdentifier]['column'] = $this->fieldConverterRegistry->getConverter($fieldTypeIdentifier)->getIndexColumn();
            }

            $fieldType = $this->fieldTypeService->getFieldType($fieldTypeIdentifier);
            $fieldMapArray[$fieldTypeIdentifier]['empty_value'] = $fieldType->getEmptyValue();
        }

        if (empty($fieldMapArray)) {
            throw new InvalidArgumentException(
                '$criterion->target',
                "No searchable fields found for the given criterion target '{$fieldIdentifier}'."
            );
        }

        return $fieldMapArray;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsFieldEmpty $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): string {
        $fieldsInformation = $this->getFieldsInformation($criterion->target);

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select('contentobject_id')
            ->from(ContentGateway::CONTENT_FIELD_TABLE, 'f_def');

        $whereExpressions = [];

        foreach ($fieldsInformation as $fieldsInfo) {
            if ($fieldsInfo['column'] === false) {
                continue;
            }

            $filterPlaceholder = $queryBuilder->createNamedParameter($fieldsInfo['empty_value']);
            $filter = $criterion->value[0]
                ? $subSelect->expr()->eq($fieldsInfo['column'], $filterPlaceholder)
                : $subSelect->expr()->neq($fieldsInfo['column'], $filterPlaceholder);

            $whereExpressions[] = $subSelect->expr()->and(
                $subSelect->expr()->in(
                    'content_type_field_definition_id',
                    $queryBuilder->createNamedParameter($fieldsInfo['ids'], Connection::PARAM_INT_ARRAY)
                ),
                $filter
            );
        }

        return $this->getInExpressionWithFieldConditions(
            $queryBuilder,
            $subSelect,
            $languageSettings,
            $whereExpressions,
            $fieldsInformation
        );
    }
}
