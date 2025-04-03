<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;

/**
 * @internal Internal exception conversion layer.
 */
final class ExceptionConversion extends Gateway
{
    private DoctrineDatabase $innerGateway;

    public function __construct(DoctrineDatabase $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function insertLanguage(Language $language): int
    {
        try {
            return $this->innerGateway->insertLanguage($language);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateLanguage(Language $language): void
    {
        try {
            $this->innerGateway->updateLanguage($language);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadLanguageListData(array $ids): iterable
    {
        try {
            return $this->innerGateway->loadLanguageListData($ids);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadLanguageListDataByLanguageCode(array $languageCodes): iterable
    {
        try {
            return $this->innerGateway->loadLanguageListDataByLanguageCode($languageCodes);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadAllLanguagesData(): array
    {
        try {
            return $this->innerGateway->loadAllLanguagesData();
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteLanguage(int $id): void
    {
        try {
            $this->innerGateway->deleteLanguage($id);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function canDeleteLanguage(int $id): bool
    {
        try {
            return $this->innerGateway->canDeleteLanguage($id);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
