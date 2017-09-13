<?php

class Emarsys_Suite2_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Don't use FPC on sync action
     */
    public function preDispatch()
    {
        if ($this->getRequest()->getActionName() == 'sync') {
            $cache = Mage::app()->getCacheInstance();
            $cache->banUse('full_page');
        }

        parent::preDispatch();
    }
    
    /**
     * Action to be called on export finished
     */
    public function syncAction()
    {
        if ($this->getRequest()->getParam('secret') == Mage::getStoreConfig('emarsys_suite2_contacts_sync/settings/notification_secret')) {
            $isTimeBased = $this->getRequest()->getParam('timebased');
            if(!isset($isTimeBased)) {
                $isTimeBased = '';
            }
            try {
                $websiteIds = explode(',', $this->getRequest()->getParam('website_ids'));
                if($isTimeBased == 1) {
                    Mage::getSingleton('emarsys_suite2/api_subscriber')->importSubscriptionUpdates($websiteIds, true);
                } else {
                    Mage::getSingleton('emarsys_suite2/api_subscriber')->importSubscriptionUpdates($websiteIds);
                    Mage::helper('emarsys_suite2/adminhtml')->scheduleCronjob('contacts');
                }

                $this->getResponse()->setBody('OK');
            } catch (Exception $e) {
                $this->getResponse()->setHeader('HTTP/1.1', '500 Error');
                $this->getResponse()->setHeader('Status', '500 Error');
                $this->getResponse()->setBody('Error');
            }
        } else {
            $this->getResponse()->setHeader('HTTP/1.1', '404 Not Found');
            $this->getResponse()->setHeader('Status', '404 File not found');
            $this->_forward('defaultNoRoute');
        }
    }
}
