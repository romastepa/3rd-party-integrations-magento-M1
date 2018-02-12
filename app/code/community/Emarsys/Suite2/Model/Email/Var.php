<?php

class Emarsys_Suite2_Model_Email_Var extends Mage_Core_Model_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/email_var');
    }
    
    /**
     * Returns mapping
     * 
     * @return array
     */
    public function getMapping()
    {
        return $this->getCollection()->getMapping();
    }
}
