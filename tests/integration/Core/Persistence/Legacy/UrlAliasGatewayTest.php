<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Legacy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway\DoctrineDatabase as LanguageGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Language\Mapper as LanguageMapper;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator as LanguageMaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway\DoctrineDatabase;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use Throwable;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway\DoctrineDatabase
 */
final class UrlAliasGatewayTest extends RepositoryTestCase
{
    private const TEXT = 'url-alias-gateway-transaction-test';

    public function testInsertRowKeepsTransactionUsableOnDuplicate(): void
    {
        $connection = $this->getIbexaTestCore()->getServiceByClassName(Connection::class);
        $gateway = $this->createGateway($connection);

        $connection->beginTransaction();

        try {
            $gateway->insertRow($this->getUrlAliasValues());

            try {
                $gateway->insertRow($this->getUrlAliasValues());
                self::fail('Expected the duplicate row to be rejected by the unique constraint');
            } catch (UniqueConstraintViolationException $e) {
                // expected: the caller is supposed to recover from it and retry
            }

            self::assertNotEmpty($gateway->loadRow(0, md5(self::TEXT)));

            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    private function createGateway(Connection $connection): DoctrineDatabase
    {
        $languageHandler = new LanguageHandler(
            new LanguageGateway($connection),
            new LanguageMapper()
        );

        return new DoctrineDatabase($connection, new LanguageMaskGenerator($languageHandler));
    }

    /**
     * @return array<string, mixed>
     */
    private function getUrlAliasValues(): array
    {
        return [
            'action' => 'nop:',
            'lang_mask' => 3,
            'link' => 1,
            'parent' => 0,
            'text' => self::TEXT,
            'text_md5' => md5(self::TEXT),
        ];
    }
}
