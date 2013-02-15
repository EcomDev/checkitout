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
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Test for abstract quote hasher
 *
 *
 */
class EcomDev_CheckItOut_Test_Model_Hash_Quote_Abstract extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Model that will be tested
     *
     * @var EcomDev_CheckItOut_Model_Hash_Quote_Abstract
     */
    protected $model = null;

    /**
     * Setting up mock for abstract class
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Test_Case::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = $this->getModelMock('ecomdev_checkitout/hash_quote_abstract', array('getDataForHash'), true);
    }

    /**
     * Test that quote object is set propertly
     *
     * @test
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Abstract::setQuote
     */
    public function setQuote()
    {
        $quote = Mage::getModel('sales/quote');
        $this->model->setQuote($quote);
        $this->assertAttributeSame($quote, '_quote', $this->model);
    }

    /**
     * Check that quote object is retrieved propertly
     *
     * @test
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Abstract::getQuote
     */
    public function getQuote()
    {
        // First time it should be null
        $this->assertNull($this->model->getQuote());

        // Setting some value to it
        $quote = Mage::getModel('sales/quote');
        $this->model->setQuote($quote);
        $this->assertSame($quote, $this->model->getQuote());

        // Setting null again
        $this->model->setQuote(null);
        $this->assertNull($this->model->getQuote());
    }

    /**
     * Check that hash is calculated propertly from data for hash
     *
     * @test
     * @dataProvider dataProvider
     * @covers EcomDev_CheckItOut_Model_Hash_Quote_Abstract::getHash
     */
    public function getHash($dataForHash)
    {
        $this->model->expects($this->once())
            ->method('getDataForHash')
            ->will($this->returnValue($dataForHash));

        $expected = md5(implode('', $dataForHash));

        $this->assertSame($expected, $this->model->getHash());
    }
}
