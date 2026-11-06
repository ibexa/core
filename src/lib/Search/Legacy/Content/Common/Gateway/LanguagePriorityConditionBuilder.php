<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;

/**
 * Builds the condition matching the field language a Content is shown in for a language priority list.
 *
 * @internal
 */
final readonly class LanguagePriorityConditionBuilder
{
    public function __construct(
        private Connection $connection,
        private LanguageHandler $languageHandler
    ) {}

    /**
     * @param array{languages?: string[]} $languageSettings
     */
    public function buildCondition(
        QueryBuilder $query,
        array $languageSettings,
        string $languageIdColumn,
        string $contentIdColumn = 'c.id',
        string $mainLanguageIdColumn = 'c.initial_language_id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        return $query->expr()->eq(
            $languageIdColumn,
            $this->buildMatchedLanguageExpression(
                $query,
                $languageSettings,
                $contentIdColumn,
                $mainLanguageIdColumn,
                $alwaysAvailableColumn
            )
        );
    }

    /**
     * Builds the expression for the id of the language a Content is shown in for a language priority list.
     *
     * @param array{languages?: string[]} $languageSettings
     */
    public function buildMatchedLanguageExpression(
        QueryBuilder $query,
        array $languageSettings,
        string $contentIdColumn = 'c.id',
        string $mainLanguageIdColumn = 'c.initial_language_id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        if (empty($languageSettings['languages'])) {
            return $mainLanguageIdColumn;
        }

        $languageIds = array_map(
            fn (string $languageCode): int => $this->languageHandler->loadByLanguageCode($languageCode)->id,
            $languageSettings['languages']
        );

        $priorityCase = 'CASE ct.language_id';
        foreach ($languageIds as $priority => $languageId) {
            $priorityCase .= sprintf(
                ' WHEN %s THEN %d',
                $query->createNamedParameter($languageId, ParameterType::INTEGER),
                $priority
            );
        }
        $priorityCase .= ' END';

        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('ct.language_id')
            ->from(ContentGateway::CONTENT_TRANSLATION_TABLE, 'ct')
            ->where(
                $subQuery->expr()->and(
                    sprintf('ct.content_id = %s', $contentIdColumn),
                    $subQuery->expr()->in(
                        'ct.language_id',
                        $query->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            )
            ->orderBy($priorityCase)
            ->setMaxResults(1);

        return sprintf(
            'COALESCE((%s), CASE WHEN %s THEN %s END)',
            $subQuery->getSQL(),
            $alwaysAvailableColumn,
            $mainLanguageIdColumn
        );
    }

    /**
     * Builds the condition matching Content translated into any of the given languages.
     *
     * @param int[] $languageIds
     */
    public function buildTranslationCondition(
        QueryBuilder $query,
        array $languageIds,
        bool $matchAlwaysAvailable,
        string $contentIdColumn = 'c.id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('1')
            ->from(ContentGateway::CONTENT_TRANSLATION_TABLE, 'ct')
            ->where(
                $subQuery->expr()->and(
                    sprintf('ct.content_id = %s', $contentIdColumn),
                    $subQuery->expr()->in(
                        'ct.language_id',
                        $query->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            );

        $condition = sprintf('EXISTS (%s)', $subQuery->getSQL());

        if (!$matchAlwaysAvailable) {
            return $condition;
        }

        return (string)$query->expr()->or(
            $condition,
            $query->expr()->eq(
                $alwaysAvailableColumn,
                $query->createNamedParameter(true, ParameterType::BOOLEAN)
            )
        );
    }
}
