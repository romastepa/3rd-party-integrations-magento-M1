<?php

class Emarsys_Suite2_Model_System_Config_Backend_Fieldmap extends Mage_Core_Model_Config_Data
{
    /**
     * @inheritdoc
     */
    protected function _beforeSave()
    {
        if ($this->isValueChanged()) {
            Mage::getSingleton('adminhtml/session')->addWarning('It is recommended to execute full customers export after changing field mapping to avoid data inconsistency.');
        }

        return $this;
    }
} 