<?php

class Emarsys_Suite2_Model_Resource_Email_Var_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
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
        $result = array();
        foreach ($this->getItems() as $item) {
            $result[$item->getMagentoCode()] = $item->getEmarsysCode();
        }

        return $result;
    }
}
