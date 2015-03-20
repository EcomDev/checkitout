<?php

class EcomDev_CheckItOut_Controller_Router
    extends Mage_Core_Controller_Varien_Router_Abstract
{
    /**
     * Initialize Controller Router
     *
     * @param Varien_Event_Observer $observer
     */
    public function initControllerRouters($observer)
    {
        /* @var $front Mage_Core_Controller_Varien_Front */
        $front = $observer->getEvent()->getFront();

        $front->addRouter('checkitout', $this);
    }

    /**
     * Validate and Match Cms Page and modify request
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::isInstalled() || !Mage::helper('ecomdev_checkitout')->isCustomRouter()) {
            return false;
        }

        $path = trim($request->getPathInfo(), '/');
        $expectedPath = trim(Mage::helper('ecomdev_checkitout')->getCustomRoute(), '/');

        if ($path != $expectedPath) {
            return false;
        }

        $request->setModuleName('checkout')
            ->setControllerName('onepage')
            ->setActionName('index')
            ->setParam('checkitout', 1);

        $request->setAlias(
            Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
            $path
        );

        return true;
    }
}