<?php

class Emarsys_Suite2_Model_System_Config_Crontab
{
    /**
     * Returns options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $list = array();
        for ($i=0; $i<24; $i+=2) {
            $list[] = array('value' => sprintf('20 %s * * *', $i), 'label' => str_pad($i, 2, '0', STR_PAD_LEFT) . ':20');
        }

        // These options should only be used in debug mode //
        if (Mage::getSingleton('emarsys_suite2/config')->getDebug()) {
            for ($i=2; $i < 10; $i+=2) {
                $list[] = array('value' => sprintf('20 */%s * * *', $i), 'label' => sprintf('Every %s hours', $i));
            }
        
            $list[] = array('value' => '*/10 * * * *', 'label' => 'Every 10 minutes');
            $list[] = array('value' => '*/30 * * * *', 'label' => 'Every 30 minutes');
        }

        return $list;
    }
}
