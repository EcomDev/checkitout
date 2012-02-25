<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

class EcomDev_CheckItOut_Test_Block_Layout_Step_Container extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Mocked block for testing abstract
     *
     * @var PHPUnit_Framework_MockObject_MockObject|EcomDev_CheckItOut_Block_Layout_Step_Container
     */
    protected $block = null;

    protected function setUp()
    {
        $this->block = $this->getBlockMock(
            'ecomdev_checkitout/layout_step_container',
            array('getSortedChildBlocks')
        );

        $shippingBlock = $this->getBlockMock(
            'ecomdev_checkitout/layout_step_default',
            array('isVisible', '_toHtml')
        );

        $shippingBlock->expects($this->any())
            ->method('isVisible')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(false),
                $this->returnValue(false)
            ));

        $shippingBlock->expects($this->any())
            ->method('_toHtml')
            ->will($this->returnValue('Shipping'));

        $billingBlock = $this->getBlockMock(
            'ecomdev_checkitout/layout_step_default',
            array('isVisible', '_toHtml')
        );

        $billingBlock->expects($this->any())
            ->method('isVisible')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(true),
                $this->returnValue(false)
            ));

        $billingBlock->expects($this->any())
            ->method('_toHtml')
            ->will($this->returnValue('Billing'));

        $this->block->expects($this->any())
            ->method('getSortedChildBlocks')
            ->will($this->returnValue(array($billingBlock, $shippingBlock)));

        $this->block->setStepCode('address');
   }

    /**
     * Checks that step count returns value correctly
     */
    public function testGetStepCount()
    {
        $this->assertSame(2, $this->block->getStepCount());
        $this->assertSame(1, $this->block->getStepCount());
        $this->assertSame(0, $this->block->getStepCount());
    }

    /**
     * Check that class names are added correctly depending ob block count
     */
    public function testAddClassNameForStepCount()
    {
        $this->assertAttributeSame(null, '_classNames', $this->block);
        $this->block->addClassNameForStepCount('two', '2');
        $this->assertAttributeSame(array('container', 'container-address', 'two'), '_classNames', $this->block);
        $this->block->removeClassName('two');
        $this->assertAttributeSame(array('container', 'container-address'), '_classNames', $this->block);
        $this->block->addClassNameForStepCount('one', '1');
        $this->assertAttributeSame(array('container', 'container-address', 'one'), '_classNames', $this->block);
        $this->block->removeClassName('one');

        // Check that one more item won't be added if count mismatch
        $this->block->addClassNameForStepCount('one', '1');
        $this->assertAttributeSame(array('container', 'container-address'), '_classNames', $this->block);
    }

    /**
     * Check that block visibility depends on child blocks
     *
     */
    public function testIsVisible()
    {
        // Shipping, billing visible
        $this->assertTrue($this->block->isVisible());
        // Billing visible
        $this->assertTrue($this->block->isVisible());
        // Nothing visible
        $this->assertFalse($this->block->isVisible());
    }

    /**
     * Check that block content depends on children
     *
     */
    public function testGetStepContent()
    {
        // Billing and shipping are visible
        $this->assertSame('BillingShipping', $this->block->getStepContent());
        // Only Billing is visible
        $this->assertSame('Billing', $this->block->getStepContent());
        // Nothing visible
        $this->assertSame('', $this->block->getStepContent());
    }
}
