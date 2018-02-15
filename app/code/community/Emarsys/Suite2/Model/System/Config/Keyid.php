<?php

class Emarsys_Suite2_Model_System_Config_Keyid
{
    /**
     * Returns options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $list = array(array('value' => 0, 'label' => "CustomerId"), array('value' => 1, 'label' => "Email"));
        return $list;
    }
}
