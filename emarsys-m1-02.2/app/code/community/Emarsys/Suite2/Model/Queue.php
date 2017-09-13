<?php
/**
 * @method \Emarsys_Suite2_Model_Resource_Queue getResource()
 **/
class Emarsys_Suite2_Model_Queue extends Mage_Core_Model_Abstract
{
    const TYPE_ENTITY_CREATE = 1;
    const TYPE_ENTITY_UPDATE = 2;
    const ENTITY_TYPE_SUBSCRIBER = 1000;
    
    protected $_entityTypes = array();
    protected $_batchSize = 1000;
    protected $_page = 0;
    protected $_collection;
    protected $_mainEntity;
    
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/queue');
    }
    
    /**
     * Returns cached collection
     * 
     * @return \Emarsys_Suite2_Model_Resource_Queue_Collection
     */
    public function getCollection()
    {
        if (!$this->_collection) {
            $this->_collection = parent::getCollection();
        }

        return $this->_collection;
    }
    
    /**
     * Sets main entity object. Used for collection methods
     * 
     * @param mixed $entity Entity
     * 
     * @return \Emarsys_Suite2_Model_Queue
     */
    public function setMainEntity($entity)
    {
        $this->_getEnityTypeId($entity);
        $this->_mainEntity = $entity;
        return $this;
    }
    
    public function removeEntities($ids)
    {
        if ($this->_mainEntity) {
            $this->getResource()->deleteIds($this->_mainEntity->getEntityTypeId(), $ids);
        }
    }
    
    /**
     * Adds entity type to entity
     * 
     * @param type $entity
     */
    public function addEntityTypeId($entity)
    {
        $this->_getEnityTypeId($entity);
    }
    
    /**
     * sets entity type id in case if its not present
     * 
     * @param mixed $entity
     */
    protected function _getEnityTypeId($entity)
    {
        if ($entity instanceof Mage_Newsletter_Model_Subscriber) {
            $entity->setEntityTypeId(self::ENTITY_TYPE_SUBSCRIBER);
        } else {
            if ($resourceName = $entity->getResourceName()) {
                if (!isset($this->_entityTypes[$resourceName])) {
                    $entityType = Mage::getSingleton('eav/entity_type')->load($resourceName, 'entity_model');
                    if ($entityType && $entityType->getId()) {
                        $this->_entityTypes[$resourceName] = $entityType->getId();
                    } else {
                        $this->_entityTypes[$resourceName] = 10000; // Unknown entity type, just to avoid multiple loading //
                    }
                }

                $entity->setEntityTypeId($this->_entityTypes[$resourceName]);
            }
        }
    }
    
    /**
     * Adds collection to export queue
     * 
     * @param mixed $collection
     */
    public function addCollection($collection)
    {
        $this->getResource()->addCollection($collection);
        return $this;
    }
    
    /**
     * adds entity to export queue
     * 
     * @param mixed $entity
     * 
     * @return type
     */
    public function addEntity($entity, $extraData = null)
    {
        if (!$entity->getEntityTypeId()) {
            $this->_getEnityTypeId($entity);
        }

        if (!$entity->hasWebsiteId()) {
            $entity->setWebsiteId(Mage::app()->getStore($entity->getStoreId())->getWebsiteId());
        }

        $this->getResource()->loadByEntity($entity, $this);
        
        if (!$this->getId()) {
            $this->setData(
                array(
                    'entity_id' => $entity->getId(),
                    'entity_type_id' => $entity->getEntityTypeId(),
                    'website_id' => $entity->getWebsiteId()
                )
            );
        }

        if ($extraData) {
            $this->setParams(serialize($extraData));
        }

        return $this->save();
    }
    
    /**
     * Removes entity from queue
     * 
     * @param mixed $entity
     * 
     * @return \Emarsys_Suite2_Model_Queue
     */
    public function removeEntity($entity)
    {
        if (!$entity->getEntityTypeId()) {
            $this->_getEnityTypeId($entity);
        }

        // Try to find entity in loaded collection first //
        if ($this->getCollection()->isLoaded()) {
            $queueItem = null;
            // Locate correct queue item //
            foreach ($this->getCollection()->getItemsByColumnValue('entity_id', $entity->getId()) as $item) {
                if ($item->getEntityTypeId() == $entity->getEntityTypeId()) {
                    $queueItem = $item;
                }
            }

            if ($queueItem) {
                $queueItem->delete();
                return $this;
            }
        }

        try {
            $this->getResource()->loadByEntity($entity, $this);
            $this->delete();
        } catch (Exception $e) {
        }

        return $this;
    }
    
    /**
     * Gets entity list for provided entity
     * 
     * @param mixed $entity
     * 
     * @return \Emarsys_Suite2_Model_Resource_Queue_Collection
     */
    protected function _getEntityList($entity)
    {
        $collection = $this->getCollection()->addFieldToFilter('entity_type_id', $entity->getEntityTypeId());
        if ($this->getEntityIds() && is_array($this->getEntityIds())) {
            $collection->addFieldToFilter('entity_id', array('IN' => $this->getEntityIds()));
        }

        return $collection;
    }
    
    /**
     * Returns queue
     * 
     * @param mixed $entity    Entity object
     * @param int   $websiteId Website identifier
     * 
     * @return \Emarsys_Suite2_Model_Resource_Queue_Collection
     */
    public function getEntityList($entity, $websiteId = 0)
    {
        if (!$entity->getEntityTypeId()) {
            $this->_getEnityTypeId($entity);
        }

        $collection = $this->_getEntityList($entity);
        
        if ($websiteId) {
            $collection->addFieldToFilter('website_id', $websiteId);
        }
        
        return $collection;
    }
    
    public function setBatchSize($batchSize)
    {
        $this->_batchSize = $batchSize;
        return $this;
    }
    
    public function resetPage()
    {
        $this->_page = 0;
    }
    
    /**
     * Gets next bunch or false if nothing left
     * 
     * @param type $entity
     * @param type $websiteId
     * @param type $queue
     * @return Emarsys_Suite2_Model_Resource_Queue_Collection|boolean
     */
    public function getNextBunch($entity, $websiteId = 0, $queue)
    {
        if ($queue) {
            if ($queue->getSize() <= ($this->_page * $this->_batchSize)) {
                return false;
            }
        }

        $this->_collection = null;
        return $this->getEntityList($entity, $websiteId)->setPageSize($this->_batchSize)->setCurPage(++$this->_page);
    }    
}
