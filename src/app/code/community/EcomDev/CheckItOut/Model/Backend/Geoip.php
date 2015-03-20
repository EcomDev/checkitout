<?php

/**
 * GeoIP config source model
 *
 * Saves data to geoip tables for extension
 */
class EcomDev_CheckItOut_Model_Backend_Geoip extends Mage_Core_Model_Config_Data
{
    const TYPE_COUNTRY = EcomDev_CheckItOut_Model_Geoip::TYPE_COUNTRY;
    const TYPE_LOCATION = EcomDev_CheckItOut_Model_Geoip::TYPE_LOCATION;
    const TYPE_LOCATION_BLOCK = EcomDev_CheckItOut_Model_Geoip::TYPE_LOCATION_BLOCK;

    /**
     * Map of configuration fields to a proper method for importing of the data
     *
     * @var array
     */
    protected $_methodsForImport = array(
        self::TYPE_COUNTRY        => 'importCountryFile',
        self::TYPE_LOCATION       => 'importLocationFile',
        self::TYPE_LOCATION_BLOCK => 'importLocationBlockFile'
    );

    /**
     * From the method for import map finds a proper method for importing of the GEOIP data
     *
     * @return EcomDev_CheckItOut_Model_Backend_Geoip|Mage_Core_Model_Abstract
     */
    public function _afterSave()
    {
        list($section, $group, $field) = explode('/', $this->getPath());

        if (!isset($this->_methodsForImport[$field])
            || empty($_FILES['groups']['name'][$group]['fields'][$field]['value'])) {
            return $this;
        }

        if (empty($_FILES['groups']['tmp_name'][$group]['fields'][$field]['value'])) {
            Mage::throwException(
                Mage::helper('ecomdev_checkitout')->__('File "%s" was not uploaded, since max upload size restrictions on your webserver. Your max upload size/max post size setting in php.ini is %s/%s',
                                                       $_FILES['groups']['name'][$group]['fields'][$field]['value'],
                                                       ini_get('upload_max_filesize'),
                                                       ini_get('post_max_size'))
            );
        }

        $csvFile = $_FILES['groups']['tmp_name'][$group]['fields'][$field]['value'];

        $model = Mage::getSingleton('ecomdev_checkitout/geoip');
        $model->{$this->_methodsForImport[$field]}($csvFile);
        return parent::_afterSave();
    }
}