<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2016 EcomDev BV (http://www.ecomdev.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Test for source model of confirmation type
 *
 *
 */
class EcomDev_CheckItOut_Test_Model_Source_Confirm_Type
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Checks available options returned by model
     *
     * @test
     * @covers EcomDev_CheckItOut_Model_Source_Confirm_Type::toOptionArray
     */
    public function checkAvailableOptions()
    {
        $options = Mage::getModel('ecomdev_checkitout/source_confirm_type')
            ->toOptionArray();

        $this->assertArrayHasKey(
            EcomDev_CheckItOut_Model_Source_Confirm_Type::TYPE_NONE,
            $options
        );

        $this->assertArrayHasKey(
            EcomDev_CheckItOut_Model_Source_Confirm_Type::TYPE_CHECKBOX,
            $options
        );

        $this->assertArrayHasKey(
            EcomDev_CheckItOut_Model_Source_Confirm_Type::TYPE_POPUP,
            $options
        );
    }
}
