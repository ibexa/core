<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType\Url\Gateway;

use Ibexa\Core\FieldType\Url\UrlStorage\Gateway;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage;
use Ibexa\Core\Persistence\Legacy\URL\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage
 */
class DoctrineStorageTest extends TestCase
{
    private DoctrineStorage $storageGateway;

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::getIdUrlMap
     */
    public function testGetIdUrlMap()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urls.php');

        $gateway = $this->getStorageGateway();

        self::assertEquals(
            [
                23 => '/content/view/sitemap/2',
                24 => '/content/view/tagcloud/2',
            ],
            $gateway->getIdUrlMap(
                [23, 24, 'fake']
            )
        );
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::getUrlIdMap
     */
    public function testGetUrlIdMap()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urls.php');

        $gateway = $this->getStorageGateway();

        self::assertEquals(
            [
                '/content/view/sitemap/2' => 23,
                '/content/view/tagcloud/2' => 24,
            ],
            $gateway->getUrlIdMap(
                [
                    '/content/view/sitemap/2',
                    '/content/view/tagcloud/2',
                    'fake',
                ]
            )
        );
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::insertUrl
     */
    public function testInsertUrl()
    {
        $gateway = $this->getStorageGateway();

        $url = 'one/two/three';
        $time = time();
        $id = $gateway->insertUrl($url);

        $query = $this->connection->createQueryBuilder();
        $query
            ->select('*')
            ->from(DoctrineDatabase::URL_TABLE)
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':id'
                )
            )
            ->setParameter('id', $id, \PDO::PARAM_INT)
        ;

        $statement = $query->executeQuery();
        $result = $statement->fetchAllAssociative();

        $expected = [
            [
                'id' => $id,
                'is_valid' => '1',
                'last_checked' => '0',
                'original_url_md5' => md5($url),
                'url' => $url,
            ],
        ];

        self::assertGreaterThanOrEqual($time, $result[0]['created']);
        self::assertGreaterThanOrEqual($time, $result[0]['modified']);

        unset($result[0]['created']);
        unset($result[0]['modified']);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::linkUrl
     */
    public function testLinkUrl()
    {
        $gateway = $this->getStorageGateway();

        $urlId = 12;
        $fieldId = 10;
        $versionNo = 1;
        $gateway->linkUrl($urlId, $fieldId, $versionNo);

        $query = $this->connection->createQueryBuilder();
        $query
            ->select('*')
            ->from(DoctrineDatabase::URL_LINK_TABLE)
            ->where(
                $query->expr()->eq($this->connection->quoteIdentifier('url_id'), ':urlId')
            )
            ->setParameter('urlId', $urlId, \PDO::PARAM_INT)
        ;

        $statement = $query->executeQuery();

        $result = $statement->fetchAllAssociative();

        $expected = [
            [
                'contentobject_attribute_id' => $fieldId,
                'contentobject_attribute_version' => $versionNo,
                'url_id' => $urlId,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::unlinkUrl
     */
    public function testUnlinkUrl()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urls.php');

        $gateway = $this->getStorageGateway();

        $fieldId = 42;
        $versionNo = 5;
        $gateway->unlinkUrl($fieldId, $versionNo);

        $query = $this->connection->createQueryBuilder();
        $query->select('*')->from(DoctrineDatabase::URL_LINK_TABLE);

        $statement = $query->executeQuery();
        $result = $statement->fetchAllAssociative();

        $expected = [
            [
                'contentobject_attribute_id' => 43,
                'contentobject_attribute_version' => 6,
                'url_id' => 24,
            ],
        ];

        self::assertEquals($expected, $result);

        // Check that orphaned URLs are correctly removed
        $query = $this->connection->createQueryBuilder();
        $query->select('*')->from(DoctrineDatabase::URL_TABLE);

        $statement = $query->executeQuery();

        $result = $statement->fetchAllAssociative();

        $expected = [
            [
                'created' => '1343140541',
                'id' => '24',
                'is_valid' => '1',
                'last_checked' => '0',
                'modified' => '1343140541',
                'original_url_md5' => 'c86bcb109d8e70f9db65c803baafd550',
                'url' => '/content/view/tagcloud/2',
            ],
        ];

        self::assertEquals($expected, $result);
    }

    protected function getStorageGateway(): Gateway
    {
        return $this->storageGateway ??= new DoctrineStorage($this->getDatabaseConnection());
    }
}
