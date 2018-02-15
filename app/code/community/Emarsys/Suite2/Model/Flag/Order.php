<?php

class Emarsys_Suite2_Model_Flag_Order extends Mage_Core_Model_Abstract
{
    /**
     * Flags order as exported
     */
    public function setExported()
    {
        $this->setIsExported(true)->save();
    }
    
    /**
     * Constructor
     * 
     * @param Mage_Sales_Model_Order $order
     */
    public function __construct(Mage_Sales_Model_Abstract $object)
    {
        parent::__construct();
        $this->load($object->getId());
        $this->setId($object->getId());
    }
    
    /**
     * @inheritdoc
     */
    public function save()
    {
        // don't save if data has nothing except the order id //
        $data = $this->getData();
        unset($data[$this->getResource()->getIdFieldName()]);
        if ($data) {
            parent::save();
        }

        return $this;
    }
    
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/flag_order');
    }
}
