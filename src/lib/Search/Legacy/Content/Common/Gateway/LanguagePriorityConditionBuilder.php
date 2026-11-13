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
final class LanguagePriorityConditionBuilder
{
    /**
     * Pre-computed integer constant which, when combined with a bit-wise AND, removes the
     * always-available bit from a language id.
     */
    private const int REMOVE_ALWAYS_AVAILABLE_BIT_OPERAND = -2;

    public function __construct(
        private readonly Connection $connection,
        private readonly LanguageHandler $languageHandler
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
        // Field language ids still carry the legacy "always available" bit until it's stripped
        // together with the language bitmask - compare them without it.
        $languageIdColumn = $this->connection->getDatabasePlatform()->getBitAndComparisonExpression(
            $languageIdColumn,
            (string)self::REMOVE_ALWAYS_AVAILABLE_BIT_OPERAND
        );

        if (empty($languageSettings['languages'])) {
            return $query->expr()->eq($languageIdColumn, $mainLanguageIdColumn);
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

        return $query->expr()->eq(
            $languageIdColumn,
            sprintf(
                'COALESCE((%s), CASE WHEN %s THEN %s END)',
                $subQuery->getSQL(),
                $alwaysAvailableColumn,
                $mainLanguageIdColumn
            )
        );
    }
}
