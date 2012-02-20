<?php

/**
 * Test case for hashing a particular quote
 *
 */
class EcomDev_CheckItOut_Test_Model_Hash_Quote_Hashers extends EcomDev_PHPUnit_Test_Case
{
    protected function setUp()
    {
        parent::setUp();
        $this->app()->disableEvents();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->app()->enableEvents();
    }

    /**
     * Test a particular hasher expected results
     *
     * @param string $modelAlias
     * @param int $quoteId
     * @loadFixture data
     * @dataProvider dataProvider
     * @doNotIndex catalog_product_attribute
     * @doNotIndex catalog_url
     * @doNotIndex catalog_product_flat
     * @doNotIndex catalog_category_flat
     * @doNotIndex catalogsearch_fulltext
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Item::getDataForHash
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Total::getDataForHash
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Address_Rate::getDataForHash
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Payment::getDataForHash
     */
    public function testHashers($modelAlias, $quoteId)
    {
        $quote = Mage::getModel('sales/quote')
            ->setStore($this->app()->getAnyStoreView())
            ->load($quoteId);

        $hasher = Mage::getModel($modelAlias);
        $hasher->setQuote($quote);
        $this->assertSame(
            $this->expected($modelAlias)->getData($quoteId),
            $hasher->getDataForHash()
        );
    }
}
