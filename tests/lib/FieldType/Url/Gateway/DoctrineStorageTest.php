<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType\Url\Gateway;

use Doctrine\DBAL\ParameterType;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage
 */
class DoctrineStorageTest extends TestCase
{
    private const string URLS_FIXTURE_FILE_PATH = __DIR__ . '/_fixtures/urls.php';
    private const string CONTENT_VIEW_SITEMAP_URL = '/content/view/sitemap/2';
    private const string CONTENT_VIEW_TAGCLOUD_URL = '/content/view/tagcloud/2';

    private DoctrineStorage $storageGateway;

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::getIdUrlMap
     */
    public function testGetIdUrlMap(): void
    {
        $this->insertDatabaseFixture(self::URLS_FIXTURE_FILE_PATH);

        $gateway = $this->getStorageGateway();

        self::assertEquals(
            [
                23 => self::CONTENT_VIEW_SITEMAP_URL,
                24 => self::CONTENT_VIEW_TAGCLOUD_URL,
            ],
            $gateway->getIdUrlMap(
                [23, 24, 'fake']
            )
        );
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::getUrlIdMap
     */
    public function testGetUrlIdMap(): void
    {
        $this->insertDatabaseFixture(self::URLS_FIXTURE_FILE_PATH);

        $gateway = $this->getStorageGateway();

        self::assertEquals(
            [
                self::CONTENT_VIEW_SITEMAP_URL => 23,
                self::CONTENT_VIEW_TAGCLOUD_URL => 24,
            ],
            $gateway->getUrlIdMap(
                [
                    self::CONTENT_VIEW_SITEMAP_URL,
                    self::CONTENT_VIEW_TAGCLOUD_URL,
                    'fake',
                ]
            )
        );
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::insertUrl
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testInsertUrl(): void
    {
        $gateway = $this->getStorageGateway();

        $url = 'one/two/three';
        $time = time();
        $id = $gateway->insertUrl($url);

        $query = $this->connection->createQueryBuilder();
        $query
            ->select('*')
            ->from('ezurl')
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':id'
                )
            )
            ->setParameter('id', $id, ParameterType::INTEGER)
        ;

        $result = $query->executeQuery()->fetchAllAssociative();

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

        unset($result[0]['created'], $result[0]['modified']);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage::linkUrl
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLinkUrl(): void
    {
        $gateway = $this->getStorageGateway();

        $urlId = 12;
        $fieldId = 10;
        $versionNo = 1;
        $gateway->linkUrl($urlId, $fieldId, $versionNo);

        $query = $this->connection->createQueryBuilder();
        $query
            ->select('*')
            ->from('ezurl_object_link')
            ->where(
                $query->expr()->eq($this->connection->quoteIdentifier('url_id'), ':urlId')
            )
            ->setParameter('urlId', $urlId, ParameterType::INTEGER)
        ;

        $result = $query->executeQuery()->fetchAllAssociative();

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
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUnlinkUrl(): void
    {
        $this->insertDatabaseFixture(self::URLS_FIXTURE_FILE_PATH);

        $gateway = $this->getStorageGateway();

        $fieldId = 42;
        $versionNo = 5;
        $gateway->unlinkUrl($fieldId, $versionNo);

        $query = $this->connection->createQueryBuilder();
        $query->select('*')->from('ezurl_object_link');

        $result = $query->executeQuery()->fetchAllAssociative();

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
        $query->select('*')->from('ezurl');

        $result = $query->executeQuery()->fetchAllAssociative();

        $expected = [
            [
                'created' => '1343140541',
                'id' => '24',
                'is_valid' => '1',
                'last_checked' => '0',
                'modified' => '1343140541',
                'original_url_md5' => 'c86bcb109d8e70f9db65c803baafd550',
                'url' => self::CONTENT_VIEW_TAGCLOUD_URL,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    protected function getStorageGateway(): Gateway
    {
        return $this->storageGateway ??= new DoctrineStorage($this->getDatabaseConnection());
    }
}
