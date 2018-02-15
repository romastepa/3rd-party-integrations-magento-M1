<?php

class Emarsys_Suite2_Model_Resource_Queue_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected $_entity2queue = array();
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/queue');
    }
    
    /**
     * Cleans loaded collection
     */
    public function clean()
    {
        if ($this->isLoaded()) {
            foreach ($this as $item) {
                $item->delete();
            }
        }
    }
    
    protected function _beforeLoad() 
    {
        parent::_beforeLoad();
        $this->_queue2entity = array();
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        foreach ($this as $item) {
            $this->_entity2queue[$item->getEntityId()] = $item->getId();
        }
    }

    /**
     * Returns queued item by entity id
     * 
     * @param type $entityId
     * 
     * @return Emarsys_Suite2_Model_Queue
     */
    public function getItemByEntityId($entityId)
    {
        if (isset($this->_entity2queue[$entityId])) {
            return $this->getItemById($this->_entity2queue[$entityId]);
        } else {
            return null;
        }
    }
    
    public function getEntityQueueId($entityId)
    {
        if ($queue = $this->getItemByEntityId($entityId)) {
            return $queue->getId();
        } else {
            return false;
        }
    }
}
