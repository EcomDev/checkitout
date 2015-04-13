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
 * @copyright  Copyright (c) 2015 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * CheckItOut OnePage Controller integration test
 * @loadSharedFixture data
 */
class EcomDev_CheckItOut_Test_Controller_OnepageController
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    const TEST_SHIPPING_METHOD = 'flatrate_flatrate';

    /**
     * Transactional email mock,
     * that are replaced by default
     * for not sending emails in the test scope
     *
     * @var Mage_Core_Model_Email_Template|PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionalEmailMock = null;


    /**
     * Checkout session mock, replaced for isolation of session object internal properities
     *
     * @var Mage_Checkout_Model_Session|PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock = null;

    /**
     * CheckItOut helper mock for controlling usage of it and passing some values
     *
     * @var EcomDev_CheckItOut_Helper_Data|PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperMock = null;

    /**
     * Replaces email template model with mock,
     * for not sending emails during test,
     * and checkout session for isolation.
     * Reads @mockHelperMethod method annotations
     * for mocking CheckItOut helper methods
     *
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Test_Case_Controller::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->transactionalEmailMock = $this->getModelMock('core/email_template', array('sendTransactional'));
        $this->replaceByMock('model', 'core/email_template', $this->transactionalEmailMock);
        $this->checkoutSessionMock = $this->getModelMock('checkout/session', array('renewSession'));
        $this->replaceByMock('singleton', 'checkout/session', $this->checkoutSessionMock);

        // Remove onepage singleton from system
        $this->replaceRegistry('_singleton/ecomdev_checkitout/type_onepage', null);
        $this->replaceRegistry('_singleton/checkout/type_onepage', null);
        $this->replaceRegistry('_singleton/customer/session', null);

        $mockedHelperMethods = $this->getAnnotationByName('mockHelperMethod');
        if ($mockedHelperMethods) {
            $this->helperMock = $this->getHelperMock('ecomdev_checkitout/data', $mockedHelperMethods);
            $this->replaceByMock('helper', 'ecomdev_checkitout', $this->helperMock);
        }
    }

    /**
     * Creates information in the session,
     * that customer is logged in
     *
     * @param string $customerEmail
     * @param string $customerPassword
     * @return Mage_Customer_Model_Session|PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCustomerSession($customerId, $storeId = null)
    {
        // Create customer session mock, for making our session singleton isolated
        $customerSessionMock = $this->getModelMock('customer/session', array('renewSession'));
        $this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

        if ($storeId === null) {
            $storeId = $this->app()->getAnyStoreView()->getCode();
        }

        $this->setCurrentStore($storeId);
        $customerSessionMock->loginById($customerId);

        return $customerSessionMock;
    }

    /**
     * Add some set of products to cart
     *
     *
     * @param array $productIds
     * @return boolean
     */
    protected function addProductsToCart(array $productIds, array $requestData = array())
    {
        $this->setCurrentStore($this->app()->getAnyStoreView());
        foreach ($productIds as $productId) {
            // Unfourtunatelly we need to load product model for each quote item,
            // because it is added as reference there
            $productModel = Mage::getModel('catalog/product')->load($productId);
            $productModel
                ->setStoreId($this->app()->getAnyStoreView()->getId())
                ->load($productId);
            if (!$productModel->getId()) {
                throw new RuntimeException(sprintf('Cannot load product #%d. Please check you dataProvider.', $productId));
            }

            if (isset($requestData[$productId]) && is_array($requestData[$productId])) {
                $request = new Varien_Object($requestData[$productId]);
            } else {
                $request = null;
            }
            $this->checkoutSessionMock->getQuote()->addProduct($productModel, $request);
        }

        $this->checkoutSessionMock->getQuote()->collectTotals();
        $this->checkoutSessionMock->getQuote()->save();

        $this->reloadQuoteInSession();

        return $this;
    }
    
    /**
     * Reloads quote in session between requests
     * 
     * @return EcomDev_CheckItOut_Test_Controller_OnepageController
     */
    protected function reloadQuoteInSession()
    {
        $this->replaceRegistry('_singleton/ecomdev_checkitout/type_onepage', null);
        $this->replaceRegistry('_singleton/checkout/type_onepage', null);
        $this->checkoutSessionMock->setQuoteId($this->checkoutSessionMock->getQuote()->getId());

        if ($this->checkoutSessionMock->getQuote() instanceof PHPUnit_Framework_MockObject_MockObject) {
            // If it is a mocked quote model, than we need to clear its data
            $this->checkoutSessionMock->getQuote()->unsetData();
            $propertiesToClear = array('_payments', '_addresses', '_items',
                                       '_customer', '_isObjectNew', '_origData');
            foreach ($propertiesToClear as $property) {
                EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                    $this->checkoutSessionMock->getQuote(),
                    $property,
                    null
                );
            }

            $this->checkoutSessionMock->getQuote()->setDataChanges(false);
        }

        // Reload quote
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->checkoutSessionMock, '_quote', null
        );
        return $this;
    }

    /**
     * Shortcut for helper method stub creation
     *
     * @param PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
     * @param PHPUnit_Framework_Constraint|string $method
     * @param mixed|PHPUnit_Framework_MockObject_Stub|null $returnValue
     * @return PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    protected function helperMethodStub(PHPUnit_Framework_MockObject_Matcher_Invocation $matcher, $method, $returnValue = null)
    {
        $methodStub = $this->helperMock->expects($matcher)
            ->method($method);

        if ($returnValue !== null) {
            if (!$returnValue instanceof PHPUnit_Framework_MockObject_Stub) {
                $returnValue = $this->returnValue($returnValue);
            }

            $methodStub->will($returnValue);
        }

        return $methodStub;
    }

    /**
     * Check that CheckItOut not loads own layout hanldes or
     * doesn't perform any actions if it is disabled
     *
     * @param array $productIds
     * @param array $requestData
     * @covers EcomDev_CheckItOut_OnepageController::indexAction
     * @covers EcomDev_CheckItOut_OnepageController::reviewAction
     * @covers EcomDev_CheckItOut_OnepageController::confirmAction
     * @covers EcomDev_CheckItOut_OnepageController::layoutAction
     * @covers EcomDev_CheckItOut_OnepageController::stepsAction
     * @covers EcomDev_CheckItOut_OnepageController::changeQtyAction
     * @covers EcomDev_CheckItOut_OnepageController::removeAction
     * @mockHelperMethod isActive
     * @mockHelperMethod isRemoveItemAllowed
     * @mockHelperMethod isChangeItemQtyAllowed
     * @loadFixture clear
     * @dataProvider dataProvider
     * @test
     */
    public function checkLayoutNotLoadedIfNotActive(array $productIds, array $requestData)
    {
        // Module functionality is not activated
        $this->helperMethodStub($this->any(), 'isActive', false);

        // It shoudn't even check item removal
        // possibility if functionality is disabled at all
        $this->helperMethodStub($this->never(), 'isRemoveItemAllowed');

        // It shoudn't even check change product qty if it is functionality is disabled
        $this->helperMethodStub($this->never(), 'isChangeItemQtyAllowed');

        // We need to add something to to be able to see checkout page
        $this->addProductsToCart($productIds, $requestData);

        // Check main layout
        $this->dispatch('checkout/onepage');

        $this->assertNotRedirect('Cart is not empty');
        $this->assertRequestControllerModule('EcomDev_CheckItOut');
        $this->assertRequestRoute('checkout/onepage/index');

        $this->assertLayoutLoaded();
        $this->assertLayoutHandleNotLoaded('ecomdev_checkitout_layout');
        $this->assertLayoutBlockRendered('checkout.onepage');

        // Steps retriving action should return 404 page
        $this->app()->resetDispatchedEvents();
        $this->dispatch('checkout/onepage/steps');
        $this->assertEventDispatched('controller_action_noroute');

        // All checkitout layout retriving action should return 404 page
        $this->app()->resetDispatchedEvents();
        $this->dispatch('checkout/onepage/layout');
        $this->assertEventDispatched('controller_action_noroute');

        // Changing of qty action should return 404 page
        $this->app()->resetDispatchedEvents();
        $this->dispatch('checkout/onepage/changeQty');
        $this->assertEventDispatched('controller_action_noroute');

        // Removing of item from cart action should return 404 page
        $this->app()->resetDispatchedEvents();
        $this->dispatch('checkout/onepage/remove');
        $this->assertEventDispatched('controller_action_noroute');

        // Start Workaround for Authorize.Net review block fatal errror
        $this->checkoutSessionMock->getQuote()
            ->getPayment()->setMethod('checkmo');
        // End Workaround for Authorize.Net review block fatal errror
        $this->dispatch('checkout/onepage/review');

        $this->assertLayoutBlockActionNotInvoked(
            'root', 'setTemplate', 'Custom Review Template should be not set',
            array('ecomdev/checkitout/review/info.phtml')
        );
    }

    /**
     * Performs layout assertions that are required for index, layout, steps actions
     *
     * @return EcomDev_CheckItOut_Test_Controller_OnepageController
     */
    protected function assertLayoutStructureForActivedFunctionality()
    {
        $this->assertLayoutBlockNotRendered('checkout.onepage');
        $this->assertLayoutBlockCreated('checkout.layout');
        $this->assertLayoutBlockTypeOf('checkout.layout', 'ecomdev_checkitout/checkout_layout');

        // Check that custom templates are set
        $this->assertLayoutBlockActionInvoked('checkout.onepage.login', 'setTemplate', array('ecomdev/checkitout/login.phtml'));
        $this->assertLayoutBlockActionNotInvoked('checkout.onepage.payment', 'setTemplate', array('ecomdev/checkitout/payment.phtml'));
        $this->assertLayoutBlockActionInvoked('checkout.onepage.review', 'setTemplate', array('ecomdev/checkitout/review.phtml'));

        // Check that skin files are included into the page layout
        $this->assertLayoutBlockActionInvoked('head', 'addItem', array('skin_js', 'js/ecomdev/checkitout.js'));
        $this->assertLayoutBlockActionInvoked('head', 'addItem', array('skin_css', 'css/ecomdev/checkitout.css'));

        // Check that confirmation step block was created
        $this->assertLayoutBlockCreated('checkout.confirm');
        $this->assertLayoutBlockTypeOf('checkout.confirm', 'checkout/onepage_review');

        return $this;
    }

    /**
     * Check that CheckItOut loads own layout hanldes for blocks
     *
     * @param array $productIds
     * @param array $requestData
     * @param array $steps
     * @covers EcomDev_CheckItOut_OnepageController::indexAction
     * @covers EcomDev_CheckItOut_OnepageController::reviewAction
     * @covers EcomDev_CheckItOut_OnepageController::confirmAction
     * @covers EcomDev_CheckItOut_OnepageController::layoutAction
     * @covers EcomDev_CheckItOut_OnepageController::stepsAction
     * @mockHelperMethod isActive
     * @loadFixture clear
     * @dataProvider dataProvider
     * @test
     */
    public function checkLayoutLoadedCorrectly(array $productIds, array $requestData, array $steps)
    {
        $this->markTestSkipped('Needs refactoring for new checkout version');
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // We need to add something to to be able to see checkout page
        $this->addProductsToCart($productIds, $requestData);

        // Check main layout
        $this->dispatch('checkout/onepage');

        $this->assertNotRedirect('Cart is not empty');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertRequestRoute('checkout/onepage/index');

        $this->assertLayoutLoaded();
        $this->assertLayoutHandleLoadedAfter('ecomdev_checkitout_layout', 'checkout_onepage_index');
        $this->assertLayoutStructureForActivedFunctionality();

        // Test that steps are returned correctly
        $this->getRequest()->setQuery('steps', $steps);
        $this->getRequest()->setQuery('isAjax', true);
        $this->dispatch('checkout/onepage/steps');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertRequestRoute('checkout/onepage/steps');
        $this->assertLayoutLoaded();
        $this->assertLayoutHandleLoadedAfter('ecomdev_checkitout_layout', 'checkout_onepage_steps');
        $this->assertLayoutStructureForActivedFunctionality();
        $this->assertResponseBodyJson();
       
        // Test that layout block is returned correctly
        $this->getRequest()->setQuery('isAjax', true);
        $this->dispatch('checkout/onepage/layout');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertRequestRoute('checkout/onepage/layout');
        $this->assertLayoutLoaded();
        $this->assertLayoutHandleLoadedAfter('ecomdev_checkitout_layout', 'checkout_onepage_layout');
        $this->assertLayoutStructureForActivedFunctionality();
        $this->assertResponseBody(
            $this->equalTo($this->getLayout()->getBlock('checkout.layout')->toHtml())
        );

        // Test that on review step there was created proper blocks
        $this->getRequest()->setQuery('isAjax', true);
        $this->dispatch('checkout/onepage/review');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertRequestRoute('checkout/onepage/review');
        $this->assertLayoutLoaded();
        $this->assertLayoutHandleLoadedAfter('ecomdev_checkitout_no_payment', 'checkout_onepage_review');
        $this->assertLayoutBlockCreated('review.fields');
        $this->assertLayoutBlockActionInvoked('root', 'setTemplate', array('ecomdev/checkitout/review/info.phtml'));

        // Also we should test that no_payment handle is not applied if payment method is selected
        $this->checkoutSessionMock->getQuote()
            ->getPayment()->setMethod('checkmo');
        $this->getRequest()->setQuery('isAjax', true);
        $this->dispatch('checkout/onepage/review');
        $this->assertLayoutHandleLoaded('checkout_onepage_review');
        $this->assertLayoutHandleNotLoaded('ecomdev_checkitout_no_payment');
        $this->assertLayoutBlockCreated('review.fields');

        // Check layout of confirmation step
        $this->getRequest()->setQuery('isAjax', true);
        $this->dispatch('checkout/onepage/confirm');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertRequestRoute('checkout/onepage/confirm');
        $this->assertLayoutLoaded();
        $this->assertLayoutHandleLoaded('checkout_onepage_confirm');
        $this->assertLayoutBlockCreated('confirm.details');
        $this->assertLayoutBlockTypeOf('confirm.details', 'checkout/onepage_progress');
        $this->assertLayoutBlockPropertyEquals('confirm.details', 'template', 'ecomdev/checkitout/confirm/details.phtml');
    }

    /**
     * Check that CheckItOut loads own layout hanldes for blocks
     *
     * @param int $customerId
     * @param array $productIds
     * @param array $requestData
     * @covers EcomDev_CheckItOut_OnepageController::indexAction
     * @mockHelperMethod isActive
     * @loadFixture clear
     * @loadFixture customers
     * @dataProvider dataProvider
     * @test
     */
    public function checkIfLoggedInCustomer($customerId, array $productIds, array $requestData)
    {
        $this->markTestSkipped('Needs refactoring for new checkout version');
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Initializing customer session
        $this->createCustomerSession($customerId);

        // Add something to cart
        $this->addProductsToCart($productIds, $requestData);

        // Check main layout
        $this->dispatch('checkout/onepage');

        $this->assertNotRedirect('Cart is not empty');
        $this->assertRequestRoute('checkout/onepage/index');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertLayoutLoaded();
        $this->assertLayoutStructureForActivedFunctionality();
        // It shouldn't render login step
        $this->assertLayoutBlockNotRendered('checkout.onepage.login');
    }

    /**
     * Test case for disabled remove product and change qty functionality
     *
     * @covers EcomDev_CheckItOut_OnepageController::changeQtyAction
     * @covers EcomDev_CheckItOut_OnepageController::removeAction
     * @mockHelperMethod isActive
     * @mockHelperMethod isRemoveItemAllowed
     * @mockHelperMethod isChangeItemQtyAllowed
     * @test
     */
    public function checkRemoveAndChangeQtyAreDisabled()
    {
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Change item qty is disabled
        $this->helperMethodStub($this->once(), 'isChangeItemQtyAllowed', false);

        // Remove item is disabled
        $this->helperMethodStub($this->once(), 'isRemoveItemAllowed', false);

        // Changing of qty action should return 404 page
        $this->app()->resetDispatchedEvents();
        $this->dispatch('checkout/onepage/changeQty');
        $this->assertEventDispatched('controller_action_noroute');

        // Removing of item from cart action should return 404 page
        $this->app()->resetDispatchedEvents();
        $this->dispatch('checkout/onepage/remove');
        $this->assertEventDispatched('controller_action_noroute');
    }

    /**
     * Test for removing items from cart
     *
     * @param array $productIds
     * @param array $requestData
     * @covers EcomDev_CheckItOut_OnepageController::removeAction
     * @loadFixture clear
     * @dataProvider dataProvider
     * @mockHelperMethod isActive
     * @mockHelperMethod isRemoveItemAllowed
     * @test
     */
    public function checkRemove(array $productIds, array $requestData)
    {
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Remove item from cart is allowed
        $this->helperMethodStub($this->once(), 'isRemoveItemAllowed', true);

        $this->addProductsToCart($productIds, $requestData);

        $itemToRemove = current($this->checkoutSessionMock->getQuote()->getAllVisibleItems());

        if (!$itemToRemove instanceof Mage_Sales_Model_Quote_Item_Abstract) {
            $this->markTestIncomplete('No item to remove for test');
        }

        $itemToRemoveId = $itemToRemove->getId();
        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setPost('item_id', $itemToRemoveId);
        $this->dispatch('checkout/onepage/remove');
        $this->assertResponseBodyJsonMatch(array('success' => true));

        $this->reloadQuoteInSession();
        $this->assertNull($this->checkoutSessionMock->getQuote()->getItemById($itemToRemoveId));
    }

    /**
     * Crash test for removing items from cart
     *
     * @param array $productIds
     * @param array $requestData
     * @covers EcomDev_CheckItOut_OnepageController::removeAction
     * @dataProvider dataProvider
     * @mockHelperMethod isActive
     * @mockHelperMethod isRemoveItemAllowed
     * @test
     */
    public function checkRemoveFailure(array $productIds, array $requestData)
    {
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Remove item from cart is allowed
        $this->helperMethodStub($this->exactly(3), 'isRemoveItemAllowed', true);

        $expectedMagentoErrorMessage = 'Test Message';
        $expectedThirdPartyErrorMessage = Mage::helper('ecomdev_checkitout')->__('There was an error during removing product from order');
        $expectedProductNotFoundMessage = Mage::helper('ecomdev_checkitout')->__('Product was not found');

        $quoteMock = $this->getModelMock('sales/quote', array('removeItem'));

        $quoteMock->expects($this->exactly(2))
            ->method('removeItem')
            ->will($this->onConsecutiveCalls(
                // Test magento exception handling
                $this->throwException(new Mage_Core_Exception($expectedMagentoErrorMessage)),
                // Test some thir-party exception handling
                $this->throwException(new Exception('Exception message that will be never shown'))
            ));

        $this->replaceByMock('model', 'sales/quote', $quoteMock);

        $this->addProductsToCart($productIds, $requestData);

        $itemToRemove = current($this->checkoutSessionMock->getQuote()->getAllVisibleItems());

        if (!$itemToRemove instanceof Mage_Sales_Model_Quote_Item_Abstract) {
            $this->markTestIncomplete('No item to remove for test');
        }

        // First test with magento exception
        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setPost('item_id', $itemToRemove->getId());
        $this->dispatch('checkout/onepage/remove');
        $this->assertResponseBodyJsonMatch(array('error' => $expectedMagentoErrorMessage));

        // Second test with thirdparty exception
        $this->dispatch('checkout/onepage/remove');
        $this->assertResponseBodyJsonMatch(array('error' => $expectedThirdPartyErrorMessage));

        // Test that it returns an error if item id is not specified
        $this->getRequest()->setPost('item_id', null);
        $this->dispatch('checkout/onepage/remove');
        $this->assertResponseBodyJsonMatch(array('error' => $expectedProductNotFoundMessage));
    }

    /**
     * Test change item qty behavior, including failure
     *
     * @param string $dataSetName
     * @param array $qtyInformation
     * @param array $productIds
     * @param array $requestData
     * @test
     * @loadFixture clear
     * @dataProvider dataProvider
     * @mockHelperMethod isActive
     * @mockHelperMethod isChangeItemQtyAllowed
     */
    public function checkChangeQty($dataSetName, array $qtyInfo, array $productIds, array $requestData)
    {
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Change item qty in cart is allowed
        $this->helperMethodStub($this->once(), 'isChangeItemQtyAllowed', true);

        // Adding products to cart
        $this->addProductsToCart($productIds, $requestData);

        $qty = current($qtyInfo);
        $productId = key($qtyInfo);

        $postData = array();
        $quoteItem = $this->checkoutSessionMock->getQuote()
            ->getItemsCollection()->getItemByColumnValue('product_id', $productId);

        $postData['item_id'] = $quoteItem ? $quoteItem->getId() : null;
        $postData['qty'] = $qty;

        $this->getRequest()->setPost($postData);
        $this->dispatch('checkout/onepage/changeQty');

        if ($this->expected($dataSetName)->hasError()) {
            $this->assertResponseBodyJsonMatch(array(
                'error' => $this->expected($dataSetName)->getError()
            ));
        } else {
            $this->assertResponseBodyJsonMatch(array(
                'success' => true
            ));
        }
    }


    /**
     * Asserts address data for expected data match
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param array $expectedData
     */
    protected function assertAddressData($address, $expectedData)
    {
        foreach ($expectedData as $field => $value) {
            $this->assertEquals(
                $value,
                $address->getDataUsingMethod($field),
                sprintf('Field "%s" in %s address matches expected value', $field, $address->getType())
            );
        }
    }

    /**
     * Test save addresses with different cases
     * that are defined in data providers and expectations
     *
     * @param string $dataSetName for mapping dataprovider and expectations
     * @param string $checkoutMethod
     * @param array $billingData
     * @param array $shippingData
     * @param array $productIds
     * @param array $requestData
     * @covers EcomDev_CheckItOut_OnepageController::saveBillingAction
     * @covers EcomDev_CheckItOut_OnepageController::saveShippingAction
     * @mockHelperMethod isActive
     * @loadFixture clear
     * @loadFixture customers
     * @dataProvider dataProvider
     * @test
     */
    public function checkSaveAddresses($dataSetName, $checkoutMethod, $billingData,
        $shippingData,  $productIds, $requestData)
    {
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Add something to our cart
        $this->addProductsToCart($productIds, $requestData);

        // Set checkout method
        $this->getOnepage()
            ->saveCheckoutMethod($checkoutMethod);

        $this->replaceRegistry('login_action_text', null);

        // Check saving billing address
        $this->getRequest()->setMethod('POST')
            ->setPost('billing', $billingData);

        $this->dispatch('checkout/onepage/saveBilling');
        $this->assertRequestRoute('checkout/onepage/saveBilling');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertNotRedirect('Request should not redirected');
        
        $this->assertAddressData(
            $this->checkoutSessionMock->getQuote()->getBillingAddress(),
            $this->expected($dataSetName)->getBillingData()
        );

        
        // If is use_for_shipping flag is check,
        // then we should check shipping address the same as billing
        if (!empty($billingData['use_for_shipping'])) {
            $this->assertAddressData(
                $this->checkoutSessionMock->getQuote()->getShippingAddress(),
                $this->expected($dataSetName)->getShippingSameAsBillingData()
            );
        }

        // If address save should fail,
        // then add additional checks for error messages
        if ($this->expected($dataSetName)->hasBillingError()) {
            $expectedError = $this->expected($dataSetName)->getBillingError();
            $this->assertResponseBodyJsonMatch($expectedError);
        } else {
            // If billing save successfull then it should return empty array
            $this->assertResponseBodyJsonNotMatch(array('error' => 1));
        }

        // Check shipping data if dataprovider contains any information about it
        if ($shippingData !== null) {
            $this->getRequest()->setMethod('POST')
                ->setPost('shipping', $shippingData);

            $this->dispatch('checkout/onepage/saveShipping');
            $this->assertRequestRoute('checkout/onepage/saveShipping');
            $this->assertRequestControllerModule($this->getModuleName());
            $this->assertNotRedirect('Request should not be redirected');

            $this->assertAddressData(
                $this->checkoutSessionMock->getQuote()->getShippingAddress(),
                $this->expected($dataSetName)->getShippingData()
            );

            // Crash test for shipping address save
            if ($this->expected($dataSetName)->hasShippingError()) {
                $expectedError = $this->expected($dataSetName)->getShippingError();
                $this->assertResponseBodyJsonMatch($expectedError);
            } else {
                $this->assertResponseBodyJsonNotMatch(array('error' => 1));
            }
        }
    }

    /**
     * Test save order with different cases
     * that are defined in data providers and expectations
     *
     * @param string $dataSetName for mapping dataprovider and expectations
     * @param string $options order creation options
     * @param array $postData
     * @param array $productIds
     * @param array $requestData
     * @covers EcomDev_CheckItOut_OnepageController::saveOrderAction
     * @mockHelperMethod isActive
     * @mockHelperMethod isCustomerCommentAllowed
     * @loadFixture clear
     * @loadFixture customers
     * @dataProvider dataProvider
     * @test
     */
    public function checkSaveOrder($dataSetName, array $options, array $postData,
        array $productIds, array $requestData)
    {
        $this->setCurrentStore($this->app()->getAnyStoreView());
        
        // Module functionality is activated
        $this->helperMethodStub($this->any(), 'isActive', true);

        // Depending on provided data allow/disallow customer comments
        $this->helperMethodStub($this->any(), 'isCustomerCommentAllowed', $options['allow_comment']);


        // Emulate customer session
        if (isset($options['customer_id'])) {
            $customerSessionMock = $this->getModelMock('customer/session', array('__construct'));
            $this->replaceByMock('model', 'customer/session', $customerSessionMock);
            $customerSessionMock->loginById($options['customer_id']);
        }
        
        // Add something to our cart
        $this->addProductsToCart($productIds, $requestData);

        // Set checkout method
        $this->getOnepage()
            ->saveCheckoutMethod($options['checkout_method']);

        if (isset($options['billing'])) {
            // Saving billing address if needed before saving order
            $result = $this->getOnepage()->saveBilling(
                $options['billing'],
                (isset($options['billing_address_id']) ? $options['billing_address_id'] : null)
            );

            $this->assertEquals(
                $this->expected($dataSetName)->getBillingResult(),
                $result
            );

            $this->reloadQuoteInSession();
        }

        if (isset($options['shipping'])) {
           // Saving shipping address if needed before saving order
            $result = $this->getOnepage()->saveShipping(
                $options['shipping'],
                (isset($options['shipping_address_id']) ? $options['shipping_address_id'] : null)
            );

            $this->assertEquals(
                $this->expected($dataSetName)->getShippingResult(),
                $result
            );

            $this->reloadQuoteInSession();
        }
        
        

        if (isset($options['shipping_method'])) {
            // Saving shipping method if needed before saving order
            $result = $this->getOnepage()->saveShippingMethod($options['shipping_method']);
            $this->getOnepage()
                ->getQuote()->save();
            $this->assertEquals(
                $this->expected($dataSetName)->getShippingMethodResult(),
                $result
            );

            $this->reloadQuoteInSession();
        }

        if (isset($options['payment'])) {
            // Saving payment if needed before saving order
            $result = $this->getOnepage()
                ->savePayment($options['payment']);

            $this->assertEquals(
                $this->expected($dataSetName)->getPaymentResult(),
                $result
            );
            
            $this->reloadQuoteInSession();
        }

        $this->getRequest()->setMethod('POST')
                ->setPost($postData);

        /* @var $createdOrder Mage_Sales_Model_Order */
        $createdOrder = false;

        $this->dispatch('checkout/onepage/saveOrder');

        if ($this->checkoutSessionMock->getLastOrderId()) {
            $createdOrder = Mage::getModel('sales/order')->load($this->checkoutSessionMock->getLastOrderId());
        }
        
        $this->assertRequestRoute('checkout/onepage/saveOrder');
        $this->assertRequestControllerModule($this->getModuleName());
        $this->assertNotRedirect('Request should not be redirected');

        $this->assertResponseBodyJsonMatch(
            $this->expected($dataSetName)->getResponse()
        );

        if ($this->expected($dataSetName)->hasOrder()) {
            $this->assertInstanceOf('Mage_Sales_Model_Order', $createdOrder);
            $expectedOrder = $this->expected('%s/%s', $dataSetName, 'order');
            // Assert billing & shipping addresses
            $this->assertAddressData($expectedOrder->getBilling(), $createdOrder->getBillingAddress());
            if ($createdOrder->getShippingAddress()) {
                $this->assertAddressData($expectedOrder->getShipping(), $createdOrder->getShippingAddress());
            }

            foreach ($expectedOrder->getInfo() as $key => $value) {
                $this->assertEquals(
                    $value,
                    $createdOrder->getDataUsingMethod($key),
                    sprintf('Order attribute "%s" should equal to expected value', $key)
                );
            }
        }
    }

    /**
     * Returns onepage singleton
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }
}
