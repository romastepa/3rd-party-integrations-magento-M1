<?php

class Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Queue extends Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Abstract
implements Varien_Data_Form_Element_Renderer_Interface
{
    protected function _prepareForm()
    {
        $result = Mage::helper('emarsys_suite2/adminhtml')->getQueueStats();
        if (empty($result)) {
            $this->_addNote('Queue is empty.', '', 'nt_emtpy');
            return $this;
        }

        foreach ($result as $websiteName => $stats) {
            $this->_addHeaderNote('Website: ' . $websiteName);
            foreach ($stats as $stat) {
                $label = $stat['entity_type'];
                $text = $stat['count'];
                $this->_addNote($label, $text, 'nt_' . $stat['website_id']. '_' . $stat['entity_type']);
            }
        }
    }
}