<?php
class Emarsys_Suite2_Model_Resource_Flag_Order extends Mage_Core_Model_Resource_Db_Abstract
{
    protected $_isPkAutoIncrement = false;
    protected $_logClassName = 'Flag_Order';
    
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/flag_order', 'order_id');
    }
    
    /**
     * Mass flagging orders
     * 
     * @param array $orderIds
     * @return boolean
     */
    public function massSetExported($objectIds)
    {
        $data = array();
        foreach ($objectIds as $objectId) {
            $data[] = array($this->getIdFieldName() => $objectId, 'is_exported' => true);
        }

        if ($data) {
            try {
                $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $data, array($this->getIdFieldName(), 'is_exported'));
            } catch (Exception $e) {
                Mage::helper('emarsys_suite2')->log($e->getMessage(), $this->_logClassName);
                return false;
            }
        }

        return true;
    }
}
