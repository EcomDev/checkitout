<?php

/**
 * Test for source model of confirmation type
 *
 *
 */
class EcomDev_CheckItOut_Test_Model_Config_Source_Confirm_Type
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Checks available options returned by model
     *
     * @test
     * @covers EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::toOptionArray
     */
    public function checkAvailableOptions()
    {
        $options = Mage::getModel('ecomdev_checkitout/config_source_confirm_type')
            ->toOptionArray();

        $this->assertArrayHasKey(
            EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::TYPE_NONE,
            $options
        );

        $this->assertArrayHasKey(
            EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::TYPE_CHECKBOX,
            $options
        );

        $this->assertArrayHasKey(
            EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::TYPE_POPUP,
            $options
        );
    }
}
