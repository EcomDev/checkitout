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
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Test for helper data loading
 *
 * @loadSharedFixture scope
 * @loadSharedFixture settings
 */
class EcomDev_CheckItOut_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Helper instance for test
     *
     * @var EcomDev_CheckItOut_Helper_Data
     */
    protected $helper = null;

    /**
     * Instantiating a helper
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Test_Case::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->helper = Mage::helper('ecomdev_checkitout');
    }

    /**
     * Test for is active method
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isActive
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isActive($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsActive(),
            $this->helper->isActive()
        );
    }

    /**
     * Test that isGuestCheckout retrieves data
     * from onepage model
     *
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isGuestCheckout
     */
    public function isGuestCheckout()
    {
        $checkoutMock = $this->getModelMockBuilder('checkout/type_onepage')
            ->disableOriginalConstructor()
            ->getMock();

        $checkoutMock->expects($this->exactly(3))
            ->method('getCheckoutMethod')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER),
                $this->returnValue(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST),
                $this->returnValue(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER)
            ));

        $this->replaceByMock('singleton', 'checkout/type_onepage', $checkoutMock);

        $this->assertFalse($this->helper->isGuestCheckout(), 'Returns false if customer is signed in');
        $this->assertTrue($this->helper->isGuestCheckout(), 'Returns true if customer is a guest');
        $this->assertFalse($this->helper->isGuestCheckout(), 'Returns false if customer is going to register');
    }

    /**
     * Checks that coupone is enabled
     *
     * @param $store
     * @dataProvider dataProvider
     * @test
     * @loadExpectation settings
     */
    public function isCouponEnabled($store)
    {
        $this->setCurrentStore($store);
        $this->assertSame(
            $this->expected($store)->getIsCouponEnabled(),
            $this->helper->isCouponEnabled()
        );
    }

    /**
     * Test that confirmation type is checkbox
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isConfirmCheckbox
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isConfirmCheckbox($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsConfirmCheckbox(),
            $this->helper->isConfirmCheckbox()
        );
    }

    /**
     * Test that confirmation type is popup window
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isConfirmPopUp
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isConfirmPopUp($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsConfirmPopUp(),
            $this->helper->isConfirmPopUp()
        );
    }

    /**
     * Test that confirmation text
     * is retrieved from configuration
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::getConfirmText
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function getConfirmText($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getConfirmText(),
            $this->helper->getConfirmText()
        );
    }

    /**
     * Test that product removals is allowed from checkout
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isRemoveItemAllowed
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isRemoveItemAllowed($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsRemoveItemAllowed(),
            $this->helper->isRemoveItemAllowed()
        );
    }

    /**
     * Test that product qty change is allowed from checkout
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isChangeItemQtyAllowed
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isChangeItemQtyAllowed($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsChangeItemQtyAllowed(),
            $this->helper->isChangeItemQtyAllowed()
        );
    }

    /**
     * Test that newsletter checkbox should be displayed
     * on order review step
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isNewsletterCheckboxDisplay
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isNewsletterCheckboxDisplay($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsNewsletterCheckboxDisplay(),
            $this->helper->isNewsletterCheckboxDisplay()
        );
    }

    /**
     * Test that newsletter checkbox should be checked by default
     * on order review step
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isNewsletterCheckboxChecked
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isNewsletterCheckboxChecked($store)
    {
        $this->setCurrentStore($store);

        $this->assertEquals(
            $this->expected($store)->getIsNewsletterCheckboxChecked(),
            $this->helper->isNewsletterCheckboxChecked()
        );
    }

    /**
     * Test that method returns valid values depending on quote store store
     *
     * @param string $store
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::isAllowedGuestCheckout
     * @loadExpectation settings
     * @dataProvider dataProvider
     */
    public function isAllowedGuestCheckout($store)
    {
        $quoteMock = $this->getModelMock('sales/quote');
        $checkoutHelperMock = $this->getHelperMock('checkout/data', array('getQuote'));

        $quoteMock->expects($this->any())
            ->method('getStoreId')
            ->will($this->returnValue(
                Mage::app()->getStore($store)->getId())
            );

        $checkoutHelperMock->expects($this->once())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

        $this->replaceByMock('helper', 'checkout', $checkoutHelperMock);

        $this->assertEquals(
            $this->expected($store)->getIsAllowedGuestCheckout(),
            $this->helper->isAllowedGuestCheckout()
        );
    }

    /**
     * Test that items info returns valid JSON with items data
     *
     * @param array $items
     * @test
     * @covers EcomDev_CheckItOut_Helper_Data::getItemsInfoJson
     * @dataProvider dataProvider
     */
    public function getItemsInfoJson($items)
    {
        $infoModelMock = $this->getModelMock('ecomdev_checkitout/quote_item_info', array('getInfo'));

        $infoModelMock->expects($this->exactly(count($items)))
            ->method('getInfo')
            ->will($this->returnArgument(0));

        $this->replaceByMock('singleton', 'ecomdev_checkitout/quote_item_info', $infoModelMock);

        $actual = $this->helper->getItemsInfoJson($items);
        $this->assertJson($actual);
        $this->assertJsonMatch($actual, $items);
    }

    /**
     * Test that extension should apply different logic for version compatibility
     *
     * @param string $store
     * @param string $type type of compatibility (key in config file)
     * @param string $currentVersion
     * @test
     * @dataProvider dataProvider
     */
    public function getCompatibilityMode($store, $type, $currentVersion)
    {
        $this->setCurrentStore($store);
        $this->assertEquals(
            $this->expected('%s-%s', $type, $store)->getData($currentVersion),
            $this->helper->getCompatibilityMode($type, $currentVersion)
        );
    }

    /**
     * Test for returning different values depending on Magento version
     *
     * @param string $store
     * @param string $type
     * @param array $values
     * @param string $currentVersion
     * @test
     * @dataProvider dataProvider
     */
    public function getCompatibleValue($store, $type, $values, $currentVersion)
    {
        $this->setCurrentStore($store);
        $this->assertEquals(
            $this->expected('%s-%s', $type, $store)->getData($currentVersion),
            $this->helper->getCompatibleValue($type, $values, $currentVersion)
        );
    }

    /**
     * Test default country value retrieval
     *
     * @param string $store
     * @test
     * @dataProvider dataProvider
     * @loadExpectation settings
     */
    public function getDefaultCountry($store)
    {
        $this->setCurrentStore($store);
        $this->assertEquals(
            $this->expected($store)->getDefaultCountry(),
            $this->helper->getDefaultCountry()
        );
    }

    /**
     * Check that shipping method is hidden
     *
     * @param string $store
     * @dataProvider dataProvider
     * @loadExpectation settings
     * @test
     */
    public function isShippingMethodHidden($store)
    {
        $this->setCurrentStore($store);
        $this->assertEquals(
            $this->helper->isShippingMethodHidden(),
            $this->expected($store)->getIsShippingMethodHidden()
        );
    }

    /**
     * Check that shipping method is hidden
     *
     * @param string $store
     * @dataProvider dataProvider
     * @loadExpectation settings
     * @test
     */
    public function isPaymentMethodHidden($store)
    {
        $this->setCurrentStore($store);
        $this->assertEquals(
            $this->helper->isPaymentMethodHidden(),
            $this->expected($store)->getIsPaymentMethodHidden()
        );
    }

    /**
     * Checks if current Magento version has Enterprise_Enterprise module
     *
     * @return bool
     */
    public function isEnterprise()
    {
        $isReallyEnterprise = $this->app()->getConfig()->getNode('modules/Enterprise_Enterprise') !== false;
        $this->assertSame($isReallyEnterprise, $this->helper->isEnterprise());
    }
}
