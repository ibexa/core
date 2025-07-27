<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializationTest extends RepositoryTestCase
{
    public function testSerialization(): void
    {
        $serializer = $this->getContainer()->get(SerializerInterface::class);
        self::assertInstanceOf(SerializerInterface::class, $serializer);
        $contentService = self::getContentService();

        $user = $contentService->loadContent(14);
        $field = $user->getField('user_account');
        self::assertNotNull($field, 'Field "name" for admin user should not be null');

        $result = $serializer->serialize($field, 'json', [JsonEncode::OPTIONS => JSON_PRETTY_PRINT]);
        $passwordHash = '$2y$10$FDn9NPwzhq85cLLxfD5Wu.L3SL3Z\/LNCvhkltJUV0wcJj7ciJg2oy';
        self::assertSame(
            <<<JSON
            {
                "id": 30,
                "fieldDefIdentifier": "user_account",
                "value": {
                    "hasStoredLogin": true,
                    "contentId": 14,
                    "login": "admin",
                    "email": "admin@link.invalid",
                    "passwordHash": "$passwordHash",
                    "passwordHashType": "7",
                    "passwordUpdatedAt": null,
                    "enabled": true,
                    "maxLogin": 10,
                    "plainPassword": null
                },
                "languageCode": "eng-US",
                "fieldTypeIdentifier": "ibexa_user",
                "fieldDefinitionIdentifier": "user_account",
                "virtual": false
            }
            JSON,
            $result,
        );
    }
}
