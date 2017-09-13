<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Model_Emarsysevents extends Mage_Core_Model_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('suite2email/emarsysevents');
    }

    /**
     * Importing events
     * @throws Exception
     */
    public function importEvents($storeId=null)
    {
        try {
            //get emarsys events and store it into array
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($websiteId);
            $apiEvents = Mage::getSingleton('emarsys_suite2/api_event')->getEvents();
            $eventArray = array();
            foreach ($apiEvents as $key => $value) {
                $eventArray[$key] = $value;
            }
            //Delete unwanted events exist in database
            $collection = Mage::getModel('suite2email/emarsysevents')->getCollection()->addFieldToFilter('website_id',array('eq'=>$websiteId));
            foreach ($collection as $coll) {
                if (!array_key_exists($coll->getEventId(), $eventArray)) {
                    $model = Mage::getModel('suite2email/emarsysevents');
                    $model->load($coll->getId());
                    $model->delete();
                }
            }
            //Update & create new events found in Emarsys
            foreach ($eventArray as $key => $value) {
                $collection = Mage::getModel('suite2email/emarsysevents')->getCollection()
                                ->addFieldToFilter("event_id", $key)
                                ->addFieldToFilter("website_id", $websiteId);
                $firstEvent = $collection->getFirstItem();
                if ($collection->getSize() && $firstEvent->getId()) {
                    $model = Mage::getModel('suite2email/emarsysevents')->load($firstEvent->getId());
                    $model->setEmarsysEvent($value);
                    $model->setWebsiteId($websiteId);
                    $model->save();
                } else {
                    $model = Mage::getModel('suite2email/emarsysevents');
                    $model->setEventId($key);
                    $model->setEmarsysEvent($value);
                    $model->setWebsiteId($websiteId);
                    $model->save();
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    public function getFirstStoreId(){
        $storeIds = array();
        foreach(Mage::app()->getStores() as $stores){
            $storeIds[] = $stores->getStoreId();
        }
        return $storeIds[0];
    }
}
