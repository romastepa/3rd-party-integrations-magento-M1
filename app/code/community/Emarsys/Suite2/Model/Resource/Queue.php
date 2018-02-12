<?php

class Emarsys_Suite2_Model_Resource_Queue extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/queue', 'id');
    }
    
    /**
     * Loads by entity
     * 
     * @param mixed                      $entity Entity to load
     * @param Emarsys_Suite2_Model_Queue $object Object to load into
     * 
     * @return \Emarsys_Suite2_Model_Resource_Queue
     */
    public function loadByEntity($entity, $object)
    {
        $select = $this->_getLoadSelect('entity_id', $entity->getId(), $object);
        $select->where('entity_type_id = ?', $entity->getEntityTypeId());

        $data = $this->_getReadAdapter()->fetchRow($select);

        if ($data) {
            $object->setData($data);
            $this->_isObjectNew = false;
        } else {
            $object->unsetData();
            $this->_isObjectNew = true;
        }

        return $this;
    }
    
    /**
     * Deletes all entities from queue by type and id
     * 
     * @param int   $entityTypeId
     * @param array $ids
     */
    public function deleteIds($entityTypeId, $ids)
    {
        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            array(
                'entity_type_id=?' => $entityTypeId,
                'entity_id IN (?)' => $ids
                )
        );
    }
    
    /**
     * Adds collection to queue
     * 
     * @param mixed $collection
     */
    public function addCollection($collection)
    {
        $items = array();
        foreach ($collection as $entity) {
            if (!$entity->getEntityTypeId()) {
                Mage::getSingleton('emarsys_suite2/queue')->addEntityTypeId($entity);
            }

            if ($entity->getWebsiteId() === null) {
                $entity->setWebsiteId(Mage::app()->getStore($entity->getStoreId())->getWebsiteId());
            }

            if ($entity->getWebsiteId()) {
                $items[] = array(
                    'entity_id' => $entity->getId(),
                    'entity_type_id' => $entity->getEntityTypeId(),
                    'website_id' => $entity->getWebsiteId()
                );
            }
        }

        if ($items) {
            $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $items);
        }
    }
}
