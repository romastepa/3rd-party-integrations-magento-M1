<?php

class Emarsys_Suite2_Model_System_Config_Crontab_Pinger
{
    /**
     * Returns options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $list = array();
        // These options should only be used in debug mode //
        $list[] = array('value' => '', 'label' => 'Disabled');
        $list[] = array('value' => '*/5 * * * *', 'label' => 'Every 5 minutes');
        $list[] = array('value' => '*/10 * * * *', 'label' => 'Every 10 minutes');
        $list[] = array('value' => '*/15 * * * *', 'label' => 'Every 15 minutes');
        $list[] = array('value' => '*/30 * * * *', 'label' => 'Every 20 minutes');
        $list[] = array('value' => '*/30 * * * *', 'label' => 'Every 30 minutes');
        return $list;
    }
}
