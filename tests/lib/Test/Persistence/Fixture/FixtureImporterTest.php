<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Test\Persistence\Fixture;

use Ibexa\Contracts\Core\Test\Persistence\Fixture;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\FixtureImporter;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FixtureImporter::class)]
final class FixtureImporterTest extends TestCase
{
    public function testStripsTheLegacyBitOnlyFromIdsThatAreNotALanguage(): void
    {
        $connection = $this->getDatabaseConnection();

        (new FixtureImporter($connection))->import(new class() implements Fixture {
            public function load(): array
            {
                return [
                    'ibexa_content_language' => [
                        ['id' => 2, 'locale' => 'eng-US', 'name' => 'English (American)', 'disabled' => 0],
                        ['id' => 5, 'locale' => 'fre-FR', 'name' => 'French', 'disabled' => 0],
                    ],
                    'ibexa_content_field' => [
                        ['id' => 1, 'version' => 1, 'contentobject_id' => 1, 'language_code' => 'eng-US', 'language_id' => 3],
                        ['id' => 2, 'version' => 1, 'contentobject_id' => 1, 'language_code' => 'fre-FR', 'language_id' => 5],
                    ],
                ];
            }
        });

        self::assertEquals(
            [[1, 2], [2, 5]],
            $connection->fetchAllNumeric('SELECT id, language_id FROM ibexa_content_field ORDER BY id')
        );
    }
}
