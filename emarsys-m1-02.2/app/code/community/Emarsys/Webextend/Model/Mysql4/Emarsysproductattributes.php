<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Model_Mysql4_Emarsysproductattributes extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('webextend/emarsysproductattributes', 'id');
    }
}
