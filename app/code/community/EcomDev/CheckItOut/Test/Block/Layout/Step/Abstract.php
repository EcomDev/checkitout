<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement for EcomDev Premium Extensions.
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.ecomdev.org/license-agreement
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

class EcomDev_CheckItOut_Test_Block_Layout_Step_Abstract extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Mocked block for testing abstract
     *
     * @var PHPUnit_Framework_MockObject_MockObject|EcomDev_CheckItOut_Block_Layout_Step_Abstract
     */
    protected $block = null;



    protected function setUp()
    {
        $this->block = $this->getBlockMock(
            'ecomdev_checkitout/layout_step_abstract',
            array(),
            true
        );
    }

    /**
     * Tests that block correctly accesses checkout block object for retrieving own step
     *
     *
     */
    public function testGetCheckoutBlock()
    {
        $expectedReturnValue = $this->getBlockMock('checkout/onepage');

        $layout = $this->getModelMock('core/layout');
        $layout->expects($this->once())
            ->method('getBlock')
            ->with('checkout.onepage')
            ->will($this->returnValue($expectedReturnValue));

        $this->block->setLayout($layout);

        $this->assertAttributeSame(null, '_checkoutBlock', $this->block);
        $this->assertSame($expectedReturnValue, $this->block->getCheckoutBlock());
        $this->assertAttributeSame($expectedReturnValue, '_checkoutBlock', $this->block);
        $this->assertSame($expectedReturnValue, $this->block->getCheckoutBlock());
        $this->assertAttributeSame($expectedReturnValue, '_checkoutBlock', $this->block);
    }

    /**
     * Check that step block is retrieved correctly
     *
     *
     */
    public function testGetStepBlock()
    {
        $stepBlock = $this->getBlockMockBuilder('checkout/onepage_billing')
            ->disableOriginalConstructor()
            ->getMock();

        $checkoutBlock = $this->getBlockMock('checkout/onepage');
        $checkoutBlock->expects($this->once())
            ->method('getChild')
            ->with('billing')
            ->will($this->returnValue($stepBlock));

        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->block, '_checkoutBlock', $checkoutBlock
        );

        $this->block->setStepCode('billing');
        $this->assertSame($stepBlock, $this->block->getStepBlock());
    }

    /**
     * Test that there should be thrown an exception if step code is not specified.
     *
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage Step code is not specified.
     */
    public function testGetStepBlockFailure()
    {
        $this->block->setStepCode(null);
        $this->block->getStepBlock();
    }

    /**
     * Tests logic for step number incrementation
     *
     */
    public function testGetStepNumber()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('EcomDev_CheckItOut_Block_Layout_Step_Abstract', '_stepNumber', 0);
        $this->assertAttributeEquals(0, '_stepNumber', 'EcomDev_CheckItOut_Block_Layout_Step_Abstract');
        $anotherBlock = $this->getBlockMock(
            'ecomdev_checkitout/layout_step_abstract',
            array(),
            true
        );
        $this->assertEquals(1, $this->block->getStepNumber());
        $this->assertAttributeEquals(1, '_stepNumber', 'EcomDev_CheckItOut_Block_Layout_Step_Abstract');
        $this->assertEquals(2, $anotherBlock->getStepNumber());
        $this->assertAttributeEquals(2, '_stepNumber', 'EcomDev_CheckItOut_Block_Layout_Step_Abstract');
        // Check that value is cached in memory
        $this->assertEquals(2, $anotherBlock->getStepNumber());
        $this->assertAttributeEquals(2, '_stepNumber', 'EcomDev_CheckItOut_Block_Layout_Step_Abstract');
    }

    /**
     * Check that loading overlay property is retrieved correctly
     */
    public function testHasLoadingOverlay()
    {
        $this->assertAttributeSame(true, '_hasLoadingOverlay', $this->block);
        $this->assertTrue($this->block->hasLoadingOverlay());
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->block, '_hasLoadingOverlay', null
        );
        $this->assertFalse($this->block->hasLoadingOverlay());
        $this->assertAttributeSame(null, '_hasLoadingOverlay', $this->block);
    }

    /**
     * Check that method getStepName should return
     * name from step object or internal property
     *
     */
    public function testGetStepName()
    {
        $checkoutSession = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(array('getStepData'))
            ->getMock();

        $expectedStep = array(
            'code' => 'billing',
            'label' => 'Billing'
        );

        $checkoutSession->expects($this->once())
            ->method('getStepData')
            ->with($this->equalTo($expectedStep['code']), $this->equalTo('label'))
            ->will($this->returnValue($expectedStep['label']));

        $this->replaceByMock('singleton', 'checkout/session', $checkoutSession);

        $this->block->setStepCode($expectedStep['code']);

        $this->assertFalse($this->block->hasData('step_name'));
        $this->assertSame($expectedStep['label'], $this->block->getStepName());
        $this->assertSame($expectedStep['label'], $this->block->getData('step_name'));

        $this->block->unsStepName();
        $this->assertFalse($this->block->hasData('step_name'));

        $this->block->setStepName('Custom Step Name');
        $this->assertSame('Custom Step Name', $this->block->getStepName());
    }

    /**
     * Checks add classnames functionality for the blocks container
     * And also initialize method
     *
     * @void
     */
    public function testAddClassName()
    {
        $this->block->setStepCode('billing');
        $this->assertAttributeSame(null, '_classNames', $this->block);
        $this->block->addClassName('test');
        $this->assertAttributeSame(array('checkout-step', 'checkout-step-billing', 'test'), '_classNames', $this->block);
        $this->block->addClassName('test2');
        $this->assertAttributeSame(array('checkout-step', 'checkout-step-billing', 'test', 'test2'), '_classNames', $this->block);
    }

    /**
     * Checks add classnames functionality for the blocks container
     *
     * @void
     */
    public function testRemoveClassName()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->block, '_classNames',
            array('checkout-step', 'checkout-step-billing', 'test', 'test2')
        );

        // Removes test without problems
        $this->block->removeClassName('test');
        $this->assertAttributeSame(array('checkout-step', 'checkout-step-billing', 'test2'), '_classNames', $this->block);
        // Removes billing step class
        $this->block->removeClassName('checkout-step-billing');
        $this->assertAttributeSame(array('checkout-step', 'test2'), '_classNames', $this->block);
        // Removes nothing
        $this->block->removeClassName('test2');
        $this->assertAttributeSame(array('checkout-step'), '_classNames', $this->block);
        // Not removes non-existent class
        $this->block->removeClassName('something');
        $this->assertAttributeSame(array('checkout-step'), '_classNames', $this->block);
        // Removes last class
        $this->block->removeClassName('checkout-step');
        $this->assertAttributeSame(array(), '_classNames', $this->block);
        // Check non failure with empty array
        $this->block->removeClassName('something');
        $this->assertAttributeSame(array(), '_classNames', $this->block);
    }

    /**
     * Test that class name is returned correctly
     *
     * @void
     */
    public function testGetClassName()
    {
        $this->block->setStepCode('billing');
        $this->assertAttributeSame(null, '_classNames', $this->block);
        $this->assertSame('checkout-step checkout-step-billing', $this->block->getClassName());
        $this->assertAttributeSame(array('checkout-step', 'checkout-step-billing'), '_classNames', $this->block);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->block, '_classNames', null
        );

        $this->block->setStepCode('address');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->block, '_initCssPrefix', 'layout'
        );

        $this->assertSame('layout layout-address', $this->block->getClassName());
        $this->assertAttributeSame(array('layout', 'layout-address'), '_classNames', $this->block);
    }

    /**
     * Check that returned step content from step block
     *
     * @void
     */
    public function testGetStepContent()
    {
        $stepBlock = $this->getBlockMockBuilder('checkout/onepage_billing')
            ->disableOriginalConstructor()
            ->setMethods(array('_toHtml'))
            ->getMock();

        $stepBlock->expects($this->once())
            ->method('_toHtml')
            ->will($this->returnValue('Test'));

        $checkoutBlock = $this->getBlockMock('checkout/onepage');
        $checkoutBlock->expects($this->once())
            ->method('getChild')
            ->with('billing')
            ->will($this->returnValue($stepBlock));

        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->block, '_checkoutBlock', $checkoutBlock
        );

        $this->block->setStepCode('billing');
        $this->assertSame('Test', $this->block->getStepContent());
        $this->assertTrue($this->block->hasData('step_content'));
        $this->assertSame('Test', $this->block->getStepContent());
    }

    /**
     * Test for being sure that visibility property is set correctly
     *
     * @void
     */
    public function testIsVisible()
    {
        $checkoutSession = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(array('getQuote'))
            ->getMock();

        $quote = $this->getModelMock('sales/quote', array('isVirtual'));

        $quote->expects($this->exactly(2))
            ->method('isVirtual')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(true), // Not visible for virtual & Virtual
                $this->returnValue(false) // Not visible for virtual & Not virtual
            ));

        $checkoutSession->expects($this->exactly(2))
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $this->replaceByMock('singleton', 'checkout/session', $checkoutSession);

        // Non visible for virtual check
        $this->block->setIsVisibleForVirtual(0);

        // Virtual
        $this->assertFalse($this->block->isVisible());

        // Not virtual stub
        $this->assertTrue($this->block->isVisible());

        // Visible for virtual stub
        $this->block->setIsVisibleForVirtual(1);

        // Should be true for virtual
        $this->assertTrue($this->block->isVisible());

        // Should be not true for not virtual
        $this->assertTrue($this->block->isVisible());
    }
}