<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Model_Mysql4_Emarsyseventsmapping extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('suite2email/emarsyseventsmapping', 'id');
    }
}
