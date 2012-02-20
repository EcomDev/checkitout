<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2011 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Adding of attributes to allow user to enter a comment to the order
 *
 */

/* @var $this EcomDev_CheckItOut_Model_Mysql4_Setup */
$this->startSetup();

$this->addAttribute('quote', 'customer_comment', array('type' => 'text'));
$this->addAttribute('order', 'customer_comment', array('type' => 'text'));

$this->endSetup();
