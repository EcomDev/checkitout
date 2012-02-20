<?php

/**
 * Hashing model for checkout steps from quote object
 *
 */
class EcomDev_CheckItOut_Model_Hash
{
    const XML_PATH_HASHABLE_STEPS = 'ecomdev/checkitout/steps';

    /**
     * Returs hash for checkout steps from quote object
     *
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function getHash($quote)
    {
        $result = array();

        $steps = Mage::getConfig()->getNode(self::XML_PATH_HASHABLE_STEPS)->children();
        foreach ($steps as $step) {
            if (!isset($step->hash)) {
                continue;
            }

            $result[$step->getName()] = '';
            foreach ($step->hash->children() as $hashModel) {
                $result[$step->getName()] .= Mage::getSingleton((string)$hashModel)
                    ->setQuote($quote)
                    ->getHash();
            }
        }

        return $result;
    }
}
