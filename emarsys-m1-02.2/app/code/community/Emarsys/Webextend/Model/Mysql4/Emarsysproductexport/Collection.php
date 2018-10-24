<?php
/**
 * @category   Webextend
 * @package    Emarsys_Webextend
 */

class Emarsys_Webextend_Model_Mysql4_Emarsysproductexport_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Define resource model
     */
    public function _construct()
    {
        $this->_init('webextend/emarsysproductexport');
    }
}