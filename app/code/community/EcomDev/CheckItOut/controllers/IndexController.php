<?php

require_once 'Mage/Checkout/controllers/IndexController.php';

/**
 * Checkitout index controller
 * Override native magento behavior with forwarding to one page checkout
 *
 */
class EcomDev_CheckItOut_IndexController extends Mage_Checkout_IndexController
{
    /**
     * CheckItOut checkout controller redirection
     *
     */
    function indexAction()
    {
        $this->_redirect('checkout/main', array('_secure'=>true));
    }
}
