<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2016 EcomDev BV (http://www.ecomdev.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Css styles dropdown
 *
 */
class EcomDev_CheckItOut_Model_Source_Design_Css
    extends EcomDev_CheckItOut_Model_Source_Design_Abstract
{
    const XML_PATH_CSS_OPTIONS = 'ecomdev/checkitout/css';

    protected $_nodePath = self::XML_PATH_CSS_OPTIONS;
}
