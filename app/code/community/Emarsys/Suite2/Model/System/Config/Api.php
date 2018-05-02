<?php

class Emarsys_Suite2_Model_System_Config_Api
{
    /**
     * Returns options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'default', 'label' => Mage::helper('emarsys_suite2')->__('Default')),
            array('value' => 'cdn', 'label' => Mage::helper('emarsys_suite2')->__('CDN')),
            array('value' => 'custom', 'label' => Mage::helper('emarsys_suite2')->__('Custom URL')),
        );
    }
}
