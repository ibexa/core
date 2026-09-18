<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType\Image\PathGenerator;

use Ibexa\Core\FieldType\Image\PathGenerator\LegacyPathGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fieldType')]
#[Group('ibexa_image')]
class LegacyPathGeneratorTest extends TestCase
{
    /**
     * @param mixed $data
     * @param mixed $expectedPath
     */
    #[DataProvider('provideStoragePathForFieldData')]
    public function testGetStoragePathForField($data, $expectedPath): void
    {
        $pathGenerator = new LegacyPathGenerator();

        self::assertEquals(
            $expectedPath,
            $pathGenerator->getStoragePathForField(
                $data['fieldId'],
                $data['versionNo'],
                $data['languageCode']
            )
        );
    }

    /**
     * @return array<mixed>
     */
    public static function provideStoragePathForFieldData(): array
    {
        return [
            [
                [
                    'fieldId' => 42,
                    'versionNo' => 1,
                    'languageCode' => 'eng-US',
                ],
                '2/4/0/0/42-1-eng-US',
            ],
            [
                [
                    'fieldId' => 23,
                    'versionNo' => 42,
                    'languageCode' => 'ger-DE',
                ],
                '3/2/0/0/23-42-ger-DE',
            ],
            [
                [
                    'fieldId' => 123456,
                    'versionNo' => 2,
                    'languageCode' => 'eng-GB',
                ],
                '6/5/4/3/123456-2-eng-GB',
            ],
        ];
    }
}
