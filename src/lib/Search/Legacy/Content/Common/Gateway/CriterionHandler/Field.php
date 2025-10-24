<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\Exception\NotFound;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry as Registry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\Converter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\Converter as FieldValueConverter;

/**
 * Field criterion handler.
 */
class Field extends FieldBase
{
    /**
     * Field converter registry.
     *
     * @var ConverterRegistry
     */
    protected $fieldConverterRegistry;

    /**
     * Field value converter.
     *
     * @var Converter
     */
    protected $fieldValueConverter;

    /**
     * Transformation processor.
     *
     * @var TransformationProcessor
     */
    protected $transformationProcessor;

    public function __construct(
        Connection $connection,
        ContentTypeHandler $contentTypeHandler,
        LanguageHandler $languageHandler,
        Registry $fieldConverterRegistry,
        FieldValueConverter $fieldValueConverter,
        TransformationProcessor $transformationProcessor
    ) {
        parent::__construct($connection, $contentTypeHandler, $languageHandler);

        $this->fieldConverterRegistry = $fieldConverterRegistry;
        $this->fieldValueConverter = $fieldValueConverter;
        $this->transformationProcessor = $transformationProcessor;
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\Field;
    }

    /**
     * Returns relevant field information for the specified field.
     *
     * The returned information is returned as an array of the attribute
     * identifier and the sort column, which should be used.
     *
     * @return array
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If no searchable fields are found for the given $fieldIdentifier.
     * @throws \RuntimeException if no converter is found
     * @throws NotFound
     */
    protected function getFieldsInformation(?string $fieldIdentifier): array
    {
        if ($fieldIdentifier === null) {
            throw new InvalidArgumentException(
                '$criterion->target',
                sprintf('Criterion target must be set for %s criterion.', Criterion\Field::class)
            );
        }

        $fieldMapArray = [];
        $fieldMap = $this->contentTypeHandler->getSearchableFieldMap();

        foreach ($fieldMap as $fieldIdentifierMap) {
            // First check if field exists in the current ContentType, there is nothing to do if it doesn't
            if (!isset($fieldIdentifierMap[$fieldIdentifier])) {
                continue;
            }

            $fieldTypeIdentifier = $fieldIdentifierMap[$fieldIdentifier]['field_type_identifier'];
            $fieldMapArray[$fieldTypeIdentifier]['ids'][] = $fieldIdentifierMap[$fieldIdentifier]['field_definition_id'];
            if (!isset($fieldMapArray[$fieldTypeIdentifier]['column'])) {
                $fieldMapArray[$fieldTypeIdentifier]['column'] = $this->fieldConverterRegistry->getConverter(
                    $fieldTypeIdentifier
                )->getIndexColumn();
            }
        }

        if (empty($fieldMapArray)) {
            throw new InvalidArgumentException(
                '$criterion->target',
                "No searchable Fields found for the provided Criterion target '{$fieldIdentifier}'."
            );
        }

        return $fieldMapArray;
    }

    /**
     * @param array $languageSettings
     * @param Criterion\Field $criterion
     *
     * @throws NotImplementedException If no searchable fields are found for the given criterion target.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFound
     * @throws NotFoundException
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

        foreach ($fieldsInformation as $fieldTypeIdentifier => $fieldsInfo) {
            if ($fieldsInfo['column'] === false) {
                continue;
            }

            $filter = $this->fieldValueConverter->convertCriteria(
                $fieldTypeIdentifier,
                $queryBuilder,
                $subSelect,
                $criterion,
                $fieldsInfo['column']
            );

            $whereExpressions[] = $subSelect->expr()->and(
                $subSelect->expr()->in(
                    'content_type_field_definition_id',
                    $queryBuilder->createNamedParameter(
                        $fieldsInfo['ids'],
                        Connection::PARAM_INT_ARRAY
                    )
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
