<?php

class Emarsys_Suite2_Model_System_Config_Mode
{
    /**
     * Returns options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value'=>'realtime',   'label'=>Mage::helper('sitemap')->__('Realtime-failsafe')),
            array('value'=>'background', 'label'=>Mage::helper('sitemap')->__('Background only')),
        );
    }
}
