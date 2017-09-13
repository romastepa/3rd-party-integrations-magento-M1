<?php
class Emarsys_Suite2_Model_Resource_Flag_Creditmemo extends Emarsys_Suite2_Model_Resource_Flag_Order
{
    protected $_logClassName = 'Flag_Creditmemo';
    
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/flag_creditmemo', 'creditmemo_id');
    }
}
