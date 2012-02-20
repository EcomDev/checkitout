<?php

/**
 * Configuration file test
 *
 */
class EcomDev_CheckItOut_Test_Config_Main extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Check that all class aliases are defined correctly
     *
     * @test
     */
    public function checkClassAliases()
    {
        $this->assertModelAlias(
            'ecomdev_checkitout/quote_item_info',
            'EcomDev_CheckItOut_Model_Quote_Item_Info'
        );

        $this->assertModelAlias(
            'ecomdev_checkitout/config_source_confirm_type',
            'EcomDev_CheckItOut_Model_Config_Source_Confirm_Type'
        );

        $this->assertHelperAlias(
            'ecomdev_checkitout',
            'EcomDev_CheckItOut_Helper_Data'
        );

        $this->assertBlockAlias(
            'ecomdev_checkitout/checkout_layout',
            'EcomDev_CheckItOut_Block_Checkout_Layout'
        );
    }

    /**
     * Check that module version and resource setup
     * configuration are set
     *
     * @test
     */
    public function checkResourceSetup()
    {
        $this->assertModuleVersion('0.1.0');

        // Assert that our resource is defined
        $this->assertConfigNodeHasChild('global/resources', 'ecomdev_checkitout_setup');

        $setupXml = new Varien_Simplexml_Element('<ecomdev_checkitout_setup />');
        $setupXml->setNode('setup/module', 'EcomDev_CheckItOut');
        $setupXml->setNode('setup/class', 'EcomDev_CheckItOut_Model_Mysql4_Setup');
        $this->assertConfigNodeSimpleXml('global/resources/ecomdev_checkitout_setup', $setupXml);
    }

    /**
     * Checks that all layout files are defined,
     * for both areas frontend and adminhtml
     *
     * @test
     */
    public function checkLayoutFileDefinitions()
    {
        $this->assertLayoutFileDefined(
            EcomDev_PHPUnit_Model_App::AREA_ADMINHTML,
            'ecomdev/checkitout.xml',
            'ecomdev_checkitout'
        );

        $this->assertLayoutFileExistsInTheme(
            EcomDev_PHPUnit_Model_App::AREA_ADMINHTML,
            'ecomdev/checkitout.xml',
            'default', 'default'
        );

        $this->assertLayoutFileDefined(
            EcomDev_PHPUnit_Model_App::AREA_FRONTEND,
            'ecomdev/checkitout.xml',
            'ecomdev_checkitout'
        );

        $this->assertLayoutFileExistsInTheme(
            EcomDev_PHPUnit_Model_App::AREA_FRONTEND,
            'ecomdev/checkitout.xml',
            'default', 'base'
        );
    }

    /**
     * Check that customer comment field is presented
     * in fieldset for copying into order
     *
     * @test
     */
    public function checkCustomerCommentFieldsets()
    {
        $this->assertConfigNodeHasChild(
            'global/fieldsets/sales_convert_quote',
            'customer_comment'
        );

        $this->assertConfigNodeValue(
            'global/fieldsets/sales_convert_quote/customer_comment/to_order',
            '*'
        );
    }

    /**
     * Checks that default configuration values are set
     *
     * @test
     * @loadExpectation someFileName.yaml
     */
    public function checkDefaultConfiguration()
    {
        $this->assertConfigNodeValue(
            'default/ecomdev_checkitout/settings/confirm_type',
            'popup'
        );

        $this->assertConfigNodeValue(
            'default/ecomdev_checkitout/settings/confirm_text',
            'I have reviewed my order and confirm that all information is correct'
        );
    }


}
