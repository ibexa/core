<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\FieldType;

/**
 * Test case for operations in the FieldTypeService using in memory storage.
 *
 * @covers \Ibexa\Contracts\Core\Repository\FieldTypeService
 *
 * @group field-type
 */
class FieldTypeServiceTest extends BaseTest
{
    /**
     * Test for the getFieldTypes() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\FieldTypeService::getFieldTypes()
     */
    public function testGetFieldTypes(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $fieldTypeService = $repository->getFieldTypeService();

        // Contains the list of all registered field types
        $fieldTypes = $fieldTypeService->getFieldTypes();
        /* END: Use Case */

        // Require at least 1 field type
        self::assertNotCount(0, $fieldTypes);

        foreach ($fieldTypes as $fieldType) {
            self::assertInstanceOf(
                FieldType::class,
                $fieldType
            );
        }
    }

    /**
     * Test for the getFieldType() method.
     *
     * Expects FieldType "ezurl" to be available!
     *
     * @covers \Ibexa\Contracts\Core\Repository\FieldTypeService::getFieldType()
     */
    public function testGetFieldType(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $fieldTypeService = $repository->getFieldTypeService();

        // Contains the "ezurl" FieldType
        $fieldType = $fieldTypeService->getFieldType('ezurl');
        /* END: Use Case */

        $this->assertInstanceof(
            FieldType::class,
            $fieldType
        );
        self::assertEquals(
            'ezurl',
            $fieldType->getFieldTypeIdentifier()
        );
    }

    /**
     * Test for the getFieldType() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\FieldTypeService::getFieldType()
     */
    public function testGetFieldTypeThrowsNotFoundException(): void
    {
        $this->expectException(\RuntimeException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $fieldTypeService = $repository->getFieldTypeService();

        // Throws and exception since type does not exist
        $fieldType = $fieldTypeService->getFieldType('sindelfingen');
        /* END: Use Case */
    }

    /**
     * Test for the hasFieldType() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\FieldTypeService::hasFieldType()
     */
    public function testHasFieldTypeReturnsTrue(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $fieldTypeService = $repository->getFieldTypeService();

        // Returns true, since 'ezurl' type exists
        $typeExists = $fieldTypeService->hasFieldType('ezurl');
        /* END: Use Case */

        self::assertTrue($typeExists);
    }

    /**
     * Test for the hasFieldType() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\FieldTypeService::hasFieldType()
     */
    public function testHasFieldTypeReturnsFalse(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $fieldTypeService = $repository->getFieldTypeService();

        // Returns false, since type does not exist
        $typeExists = $fieldTypeService->hasFieldType('sindelfingen');
        /* END: Use Case */

        self::assertFalse($typeExists);
    }
}
