<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Section;

use Ibexa\Contracts\Core\Persistence\Content\Section;
use Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Section\Handler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Section\Handler
 */
class SectionHandlerTest extends TestCase
{
    /**
     * Section handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Section\Handler
     */
    protected $sectionHandler;

    /**
     * Section gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Section\Gateway
     */
    protected $gatewayMock;

    public function testCreate()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('insertSection')
            ->with(
                self::equalTo('New Section'),
                self::equalTo('new_section')
            )->will(self::returnValue(23));

        $sectionRef = new Section();
        $sectionRef->id = 23;
        $sectionRef->name = 'New Section';
        $sectionRef->identifier = 'new_section';

        $result = $handler->create('New Section', 'new_section');

        self::assertEquals(
            $sectionRef,
            $result
        );
    }

    public function testUpdate()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('updateSection')
            ->with(
                self::equalTo(23),
                self::equalTo('New Section'),
                self::equalTo('new_section')
            );

        $sectionRef = new Section();
        $sectionRef->id = 23;
        $sectionRef->name = 'New Section';
        $sectionRef->identifier = 'new_section';

        $result = $handler->update(23, 'New Section', 'new_section');

        self::assertEquals(
            $sectionRef,
            $result
        );
    }

    public function testLoad()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadSectionData')
            ->with(
                self::equalTo(23)
            )->will(
                self::returnValue(
                    [
                        [
                            'id' => '23',
                            'identifier' => 'new_section',
                            'name' => 'New Section',
                        ],
                    ]
                )
            );

        $sectionRef = new Section();
        $sectionRef->id = 23;
        $sectionRef->name = 'New Section';
        $sectionRef->identifier = 'new_section';

        $result = $handler->load(23);

        self::assertEquals(
            $sectionRef,
            $result
        );
    }

    public function testLoadAll()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
        ->method('loadAllSectionData')
        ->will(
            self::returnValue(
                [
                    [
                        'id' => '23',
                        'identifier' => 'new_section',
                        'name' => 'New Section',
                    ],
                    [
                        'id' => '46',
                        'identifier' => 'new_section2',
                        'name' => 'New Section2',
                    ],
                ]
            )
        );

        $sectionRef = new Section();
        $sectionRef->id = 23;
        $sectionRef->name = 'New Section';
        $sectionRef->identifier = 'new_section';

        $sectionRef2 = new Section();
        $sectionRef2->id = 46;
        $sectionRef2->name = 'New Section2';
        $sectionRef2->identifier = 'new_section2';

        $result = $handler->loadAll();

        self::assertEquals(
            [$sectionRef, $sectionRef2],
            $result
        );
    }

    public function testLoadByIdentifier()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadSectionDataByIdentifier')
            ->with(
                self::equalTo('new_section')
            )->will(
                self::returnValue(
                    [
                        [
                            'id' => '23',
                            'identifier' => 'new_section',
                            'name' => 'New Section',
                        ],
                    ]
                )
            );

        $sectionRef = new Section();
        $sectionRef->id = 23;
        $sectionRef->name = 'New Section';
        $sectionRef->identifier = 'new_section';

        $result = $handler->loadByIdentifier('new_section');

        self::assertEquals(
            $sectionRef,
            $result
        );
    }

    public function testDelete()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('countContentObjectsInSection')
            ->with(self::equalTo(23))
            ->will(self::returnValue(0));

        $gatewayMock->expects(self::once())
            ->method('deleteSection')
            ->with(
                self::equalTo(23)
            );

        $result = $handler->delete(23);
    }

    public function testDeleteFailure()
    {
        $this->expectException(\RuntimeException::class);

        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('countContentObjectsInSection')
            ->with(self::equalTo(23))
            ->will(self::returnValue(2));

        $gatewayMock->expects(self::never())
            ->method('deleteSection');

        $result = $handler->delete(23);
    }

    public function testAssign()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('assignSectionToContent')
            ->with(
                self::equalTo(23),
                self::equalTo(42)
            );

        $result = $handler->assign(23, 42);
    }

    public function testPoliciesCount()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('countPoliciesUsingSection')
            ->with(
                self::equalTo(1)
            )
            ->will(
                self::returnValue(7)
            );

        $result = $handler->policiesCount(1);
    }

    public function testCountRoleAssignmentsUsingSection()
    {
        $handler = $this->getSectionHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('countRoleAssignmentsUsingSection')
            ->with(
                self::equalTo(1)
            )
            ->will(
                self::returnValue(0)
            );

        $handler->countRoleAssignmentsUsingSection(1);
    }

    /**
     * Returns the section handler to test.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Section\Handler
     */
    protected function getSectionHandler()
    {
        if (!isset($this->sectionHandler)) {
            $this->sectionHandler = new Handler(
                $this->getGatewayMock()
            );
        }

        return $this->sectionHandler;
    }

    /**
     * Returns a mock for the section gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Section\Gateway
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->gatewayMock;
    }
}
