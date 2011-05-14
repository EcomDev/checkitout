<?php

/**
 * Adding of attributes to allow user to enter a comment to the order
 *
 */

/* @var $this EcomDev_CheckItOut_Model_Mysql4_Setup */
$this->startSetup();

$this->addAttribute('quote', 'customer_comment', array('type' => 'text'));
$this->addAttribute('order', 'customer_comment', array('type' => 'text'));

$this->endSetup();
