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
 * Test case for a whole hashing checkout steps via hash models
 *
 */
class EcomDev_CheckItOut_Test_Model_Hash extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Setting up hashers mocks
     * (non-PHPdoc)
     * @cache off
     * @see EcomDev_PHPUnit_Test_Case::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $hashQuoteItem = $this->getModelMock('ecomdev_checkitout/hash_quote_item');
        $hashQuoteTotal = $this->getModelMock('ecomdev_checkitout/hash_quote_total');
        $hashQuotePayment = $this->getModelMock('ecomdev_checkitout/hash_quote_payment');
        $hashQuoteAddressRate = $this->getModelMock('ecomdev_checkitout/hash_quote_address_rate');

        $hashQuoteItem->expects($this->exactly(2))
            ->method('getHash')
            ->will($this->returnValue('items'));

        $hashQuoteTotal->expects($this->exactly(2))
            ->method('getHash')
            ->will($this->returnValue('totals'));

        $hashQuotePayment->expects($this->once())
            ->method('getHash')
            ->will($this->returnValue('payment'));

        $hashQuoteAddressRate->expects($this->once())
            ->method('getHash')
            ->will($this->returnValue('rates'));

        foreach (array($hashQuoteAddressRate, $hashQuotePayment,
                       $hashQuoteTotal, $hashQuoteItem) as $mock) {
            $mock->expects($this->any())
                ->method('setQuote')
                ->with($this->isInstanceOf('Mage_Sales_Model_Quote'))
                ->will($this->returnValue($mock));

        }

        $this->replaceByMock('singleton', 'ecomdev_checkitout/hash_quote_item', $hashQuoteItem);
        $this->replaceByMock('singleton', 'ecomdev_checkitout/hash_quote_total', $hashQuoteTotal);
        $this->replaceByMock('singleton', 'ecomdev_checkitout/hash_quote_payment', $hashQuotePayment);
        $this->replaceByMock('singleton', 'ecomdev_checkitout/hash_quote_address_rate', $hashQuoteAddressRate);

        // If someone wants to customize hashers logic
        // This event should help resolve failed test
        Mage::dispatchEvent('ecomdev_checkitout_test_hash_setup', array('test_case' => $this));
    }

    /**
     * Retrieves hash full hash from hash models
     *
     * @param int $quoteId
     * @doNotIndex catalog_product_attribute
     * @doNotIndex catalog_url
     * @doNotIndex catalog_product_flat
     * @doNotIndex catalog_category_flat
     * @doNotIndex catalogsearch_fulltext
     * @covers EcomDev_CheckItOut_Model_Hash::getHash
     * @test
     * @cache off all
     * @cache on config
     */
    public function getHash()
    {
        $quote = $this->getModelMock('sales/quote');

        $this->assertSame(
            $this->expected()->getData(),
            Mage::getModel('ecomdev_checkitout/hash')->getHash($quote)
        );
    }
}
