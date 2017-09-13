<?php

class Emarsys_Suite2_Model_Order extends Varien_Object
{
    const BATCH_SIZE = 10;
    
    public function export()
    {
        foreach (Mage::app()->getWebsites() as $website) {
            $this->_exportWebsite($website);
        }
    }
}
