<?php
class Emarsys_Suite2_Model_Resource_Email_Event extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/email_event', 'id');
    }
    
    /**
     * Deletes all records except eventIds array
     * 
     * @param array $eventIds
     */
    public function deleteExcept($eventIds)
    {
        foreach (Mage::getResourceSingleton('emarsys_suite2/email_event_collection')->addFieldToFilter('event_id', array('nin' => $eventIds)) as $event) {
            $event->delete();
        }
    }
    
    /**
     * Deletes all records
     */
    public function deleteAll()
    {
        foreach (Mage::getResourceSingleton('emarsys_suite2/email_event_collection') as $event) {
            $event->delete();
        }
    }
}
