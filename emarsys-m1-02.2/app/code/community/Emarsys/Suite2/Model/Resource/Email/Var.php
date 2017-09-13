<?php
class Emarsys_Suite2_Model_Resource_Email_Var extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/email_var', 'magento_code');
    } 
}
