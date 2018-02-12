<?php

class Emarsys_Suite2_Model_Resource_Email_Event_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('emarsys_suite2/email_event');
    }
}
