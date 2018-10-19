<?php

class Emarsys_Suite2_Model_Observer
{
    const CUSTOMER_SAVE_TRIGGER = 'CUSTOMER_SAVE_TRIGGER';
    
    protected function _isEnabled()
    {
        return Mage::getSingleton('emarsys_suite2/config')->isEnabled();
    }

    /**
     * Returns per-event custom data
     * 
     * @param type $customer
     * @param type $eventName
     * @return type
     */
    protected function _getCustomData($customer, $eventName = null)
    {
        switch ($eventName) {
            case 'customer_login':
//                $dt =  Mage::app()->getLocale()->date()->toString('YYYY-MM-dd HH:mm:ss');
                // Server time needed here
                $dt = new Zend_Date();
                $dt = $dt->toString('YYYY-MM-dd HH:mm:ss');
                $result = array('c_last_login' => $dt);
                break;
            case 'sales_order_save_commit_after':
                $result = Mage::helper('emarsys_suite2')->getCustomersOrdersData($customer);
                break;
            case 'checkout_cart_product_add_after':
                $result = Mage::helper('emarsys_suite2')->getCustomerCartData($customer);
                break;
            default:
                $result = null;
        }

        return $result;
    }
    
    /**
     * 
     * @param type $customer
     * @return type
     */
    protected function _getCustomerSaveTriggered($customer)
    {
        $email = ($customer->getEmail() ? $customer->getEmail() : $customer->getSubscriberEmail());
        return Mage::registry(self::CUSTOMER_SAVE_TRIGGER . '_' . md5($email));
    }
    
    /**
     * 
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function _setCustomerSaveTriggered($customer)
    {
        $key = self::CUSTOMER_SAVE_TRIGGER . '_' . md5($customer->getEmail());
        if (!Mage::registry($key)) {
            Mage::register($key, 1);
        }
    }
    
    /**
     * Tries to set customer's subscriber ID for API
     * 
     * @param Mage_Catalog_Model_Customer $customer
     */
    protected function _getSubscriber($customer)
    {
        $subscriber = false;
        if (!$customer->hasSubscriberId()) {
            $subscriber = Mage::getModel('newsletter/subscriber')->load($customer->getId(), 'customer_id');
            $this->_addSubscriberDataToCustomer(
                $customer,
                $subscriber
            );
        }

        return $subscriber;
    }
    
    /**
     * Adds subscriber data to customer object
     * 
     * @param type $customer
     * @param type $subscriber
     */
    protected function _addSubscriberDataToCustomer($customer, $subscriber)
    {
        if ($customer && $subscriber && $customer->getId() && $subscriber->getId()) {
            $config = Mage::getSingleton('emarsys_suite2/config');
            if (Mage::app()->getStore()->isAdmin()) {
                $config->setWebsite($customer->getWebsiteId());
            } else {
                $config->setWebsite(Mage::app()->getWebsite());
            }

            $customer->setSubscriberId($subscriber->getId());
            if (!$customer->hasIsSubscribed()) {
                $customer->setIsSubscribed(($subscriber->getSubscriberStatus() == 1) ? true : false);
            }
        }
    }
    
    /**
     * Triggered when creditmemo is updated
     */
    public function creditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled()) {
            return $this;
        }

        $creditmemo = $observer->getCreditmemo();
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        // Don't export creditmemos that were already exported
        if (Mage::getModel('emarsys_suite2/flag_creditmemo', $creditmemo)->getIsExported()) {
            return $this;
        }

        $order = $creditmemo->getOrder();
        
        if ($order->getCustomerIsGuest()
            && !Mage::getStoreConfig('emarsys_suite2_smartinsight/settings/guest_export', $order->getStoreId())) {
            return;
        }

        if ($creditmemo->getState() == Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED) {
            Mage::getSingleton('emarsys_suite2/queue')->addEntity($creditmemo);
        }
    }
    
    /**
     * Triggered after save commit, sends order and customer to Suite
     */
    public function orderSaveAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled()) {
            return $this;
        }
        
        $order = $observer->getData('order');
        // Don't export orders that were already exported
        if (Mage::getModel('emarsys_suite2/flag_order', $order)->getIsExported()) {
            return $this;
        }

        // Do not export guest orders unless backend setting allow this
        if ($order->getCustomerIsGuest()
            && !Mage::getStoreConfig('emarsys_suite2_smartinsight/settings/guest_export', $order->getStoreId())) {
            return;
        }
        
        Varien_Profiler::start('EmarsysSuite2::orderSaveAfter');
        /* @var $order Mage_Sales_Model_Order */
        // if order is paid, queue it up and export customer //
        if (in_array($order->getState(), Mage::helper('emarsys_suite2')->getPaidOrderStates())) {
            Mage::getModel('emarsys_suite2/queue')->addEntity($order);
        }

        if (($customerId = $order->getCustomerId()) && 
                ($customer = Mage::getModel('customer/customer')->load($customerId)) &&
                ($customer->getId())
                ) {
            // add customer to observer and forward event further to customerSaveAfter
            $observer->setCustomer($customer);
            $this->customerSaveAfter($observer);
        }

        Varien_Profiler::stop('EmarsysSuite2::orderSaveAfter');
    }

    /**
     * Storing the updated time when there is change in the newsletter subscription status
     * @param Varien_Event_Observer $observer
     */
     public function subscriberSaveBefore(Varien_Event_Observer $observer){
         $subscriber = $observer->getSubscriber();
         $website = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();
         if($website){
             Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
         }
         if (!$this->_isEnabled()) {
            return;
        }

        $subscriber = $observer->getSubscriber();

        $subscriberBeforeChange = Mage::getModel('newsletter/subscriber')->load($subscriber->getSubscriberId());

        if($subscriberBeforeChange->getSubscriberId()) {
            $subscriber->setOrigData('subscriber_status', $subscriberBeforeChange->getSubscriberStatus());
        }

        /* Set status change time */
        $beforeChange = $subscriberBeforeChange->getSubscriberStatus();
        $afterChange = $subscriber->getSubscriberStatus();
        if ($beforeChange != $afterChange) {
            $subscriber['change_status_at'] = (date("Y-m-d H:i:s", time()));
        }

    }
    
    /**
     * Send or schedule subscriber info via API
     */
    public function subscriberSaveAfter(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        $website = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();
        if($website){
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
        }
        if (!$this->_isEnabled()) {
            return;
        }
        
        $subscriber = $observer->getSubscriber();

        // Skip the Subscriber Sync if there is no change in subscriber_status
        if($subscriber->getSubscriberStatus() == $subscriber->getOrigData('subscriber_status')){
            return;
        }
        
        // Dispatches event which allows to set flag emarsys_no_export //
        Mage::dispatchEvent('emarsys_before_subscriber_export', array('subscriber' => $subscriber));
        
        if ($subscriber->getEmarsysNoExport() || $subscriber->getEmarsysNoObserve() /*|| $this->_getCustomerSaveTriggered($subscriber)*/) {
            return;
        }

        Varien_Profiler::start('EmarsysSuite2::subscriberSaveAfter');

        $config = Mage::getSingleton('emarsys_suite2/config');
        if (Mage::app()->getStore()->isAdmin()) {
            $config->setWebsite(Mage::app()->getStore($subscriber->getStoreId())->getWebsite());
        } else {
            $config->setWebsite(Mage::app()->getWebsite());
        }

        if (Mage::getSingleton('emarsys_suite2/config')->getSyncMode() == 'realtime' || $subscriber->isObjectNew()) {
            $subscriber->setIsSubscribed(($subscriber->getSubscriberStatus() == 1) ? 1 : 0);
            Mage::helper("emarsys_suite2/timeBasedOptinSync")->realtimeTimeBasedOptinSync($subscriber);
            Mage::getModel('emarsys_suite2/api_subscriber')->exportOne($subscriber);
        } else {
            // Queue if mode is backgroud
            Mage::getSingleton('emarsys_suite2/queue')->addEntity($subscriber);
        }

        Varien_Profiler::stop('EmarsysSuite2::subscriberSaveAfter');
    }

    /**
     * check if subscriber id exists for this customer so we can override in future
     */
    public function customerSaveBefore(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        $website = $customer->getWebsiteId();
        if($website){
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
        }
        if (!$this->_isEnabled()) {
            return $this;
        }

        Varien_Profiler::start('EmarsysSuite2::customerSaveBefore');
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $observer->getCustomer();
        // if customer requires confirmation, then forced export must be triggered only when customer is just confirmed
        if ($customer->isConfirmationRequired()) {
            $isCustomerExportForceNeeded = (!$customer->getConfirmation() && ($customer->getConfirmation()!= $customer->getOrigData('confirmation')));
        } else {
            $isCustomerExportForceNeeded = $customer->isObjectNew();
            $customer->setIsNewCustomer(true);
        }

        if ($isCustomerExportForceNeeded) {
            $customer->setForceRealtimeExport(true);
            if (!Mage::app()->getStore()->isAdmin()) {
                // Force last login to be the creation date if created via frontend //
                $dt = new Zend_Date();
                $dt = $dt->toString('YYYY-MM-dd HH:mm:ss');
                $customer->setData('c_last_login', $dt);
            }
        }

        // Get existing subscriber_id if possible
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())->getStoreIds();
        if ($subscriber = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('store_id', array('IN' => $storeIds))
            ->addFieldToFilter('subscriber_email', $customer->getEmail())
            ->getFirstItem()) {
            $customer->setSubscriberId($subscriber->getId());
        }

        if ($customer->getSubscriberId()) {
            // Register flag that this customer save was already triggered to avoid any other triggers //
            $this->_setCustomerSaveTriggered($customer);
        }

        Varien_Profiler::stop('EmarsysSuite2::customerSaveBefore');
    }

    /**
     * Send or schedule customer info via API
     */
    public function customerSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        $website = $customer->getWebsiteId();
        if($website){
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
        }
        if (!$this->_isEnabled()) {
            return;
        }

        Varien_Profiler::start('EmarsysSuite2::customerSaveAfter');
        $customer = $observer->getCustomer();

        Mage::dispatchEvent('emarsys_before_customer_export', array('customer' => $customer));
        if ($customer->getEmarsysNoExport())
        {
            return;
        }

        /* @var $customer Mage_Customer_Model_Customer */
        $extraData = $this->_getCustomData($customer, $observer->getEvent()->getName());
        /*
        if ($customer->getSubscriberId()) {
            $extraData[Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection::EMARSYS_SUBSCRIBER_UPDATE_FLAG] = true;
            $subscriber = Mage::getModel('newsletter/subscriber')->load($customer->getSubscriberId());
            if ($subscriber->getId()) {
                Mage::getSingleton('emarsys_suite2/queue')->removeEntity($subscriber);
            }
        }
        */
        if (!$customer->isObjectNew() && $customer->getOrigData('email') != $customer->getData('email')) {
            $extraData[Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection::EMARSYS_MAIL_CHANGE_FROM] = $customer->getOrigData('email');
        }

        if ($customer->getEmarsysExportProcessed()) {
            Varien_Profiler::stop('EmarsysSuite2::customerSaveAfter');
            return $this;
        }

        $this->_getSubscriber($customer);

        if ($customer->getForceRealtimeExport() || Mage::getSingleton('emarsys_suite2/config')->getSyncMode() == 'realtime') {
            Mage::getModel('emarsys_suite2/api_customer')->exportOne($customer, $extraData);
        } else {
            Mage::getSingleton('emarsys_suite2/queue')->addEntity($customer, $extraData);
        }

        $customer->setEmarsysExportProcessed(true);
        Varien_Profiler::stop('EmarsysSuite2::customerSaveAfter');
        return $this;
    }

    /**
     * Send or schedule customer info via API
     */
    public function customerAddressSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomerAddress()->getCustomer();
        // No need to trigger when there was a triggered save on this customer or when export was done already //
        if (!$this->_isEnabled() || $this->_getCustomerSaveTriggered($customer) || $customer->getEmarsysExportProcessed()) {
            return;
        }

        Varien_Profiler::start('EmarsysSuite2::customerAddressSaveAfter');
        if (Mage::getSingleton('emarsys_suite2/config')->getSyncMode() == 'realtime') {
            $this->_getSubscriber($customer);
            Mage::getModel('emarsys_suite2/api_customer')->exportOne($customer);
        } else {
            Mage::getSingleton('emarsys_suite2/queue')->addEntity($customer);
        }

        $customer->setEmarsysExportProcessed(true);
        Varien_Profiler::stop('EmarsysSuite2::customerAddressSaveAfter');
        return $this;
    }

    public function exportOrders()
    {
        if ($this->_isEnabled()) {
            Mage::getSingleton('emarsys_suite2/api_order')->export();
        }
    }

    /**
     * Exports customers
     */
    public function exportContacts()
    {
        Mage::getSingleton('emarsys_suite2/api_subscriber')->export();
        Mage::getSingleton('emarsys_suite2/api_customer')->export();
    }

    public function updateLastModifiedContacts($websiteId)
    {
        try {

            $currentPageNumber = 1;
            $subscriberQueueCollection = Mage::getModel('emarsys_suite2/queue')->getCollection()
                                            ->addFieldToFilter('website_id',array('eq'=>$websiteId))
                                            ->addFieldToFilter('entity_type_id', array('eq' => '1000'))
                                            ->setPageSize(Emarsys_Suite2_Model_Api_Abstract::BATCH_SIZE)
                                            ->setCurPage($currentPageNumber)
                                            ;

            $lastPageNumber = $subscriberQueueCollection->getLastPageNumber();

            while($currentPageNumber <= $lastPageNumber){
                $subscriberIds = array();
                if($currentPageNumber != 1) {
                    $subscriberQueueCollection = Mage::getModel('emarsys_suite2/queue')->getCollection()
                        ->addFieldToFilter('website_id',array('eq'=>$websiteId))
                        ->addFieldToFilter('entity_type_id', array('eq' => '1000'))
                        ->setPageSize(Emarsys_Suite2_Model_Api_Abstract::BATCH_SIZE)
                        ->setCurPage($currentPageNumber)
                    ;
                }

                if (count($subscriberQueueCollection)) {
                    $subscriberIds = $subscriberQueueCollection->getColumnValues('entity_id');
                    //print_r($subscriberIds); exit;
                    if (count($subscriberIds)) {
                        Mage::helper("emarsys_suite2/timeBasedOptinSync")->backgroudTimeBasedOptinSync($subscriberIds, $websiteId);
                    }
                }
                $currentPageNumber = $currentPageNumber+1;

            }

        } catch (\Exception $e) {
            printf("error(updateLastModifiedContacts) %s" , $e->getMessage());
        }
    }

    /**
     * Cron method used to sync all data
     */
    public function syncContactsData()
    {
        $queue = array();
        foreach (Mage::app()->getWebsites() as $website) {
            try {
                $config = Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
                /* @var $config Emarsys_Suite2_Model_Config */
                if ($this->_isEnabled()) {
                    if ($config->getContactsSyncOrder() == Emarsys_Suite2_Model_Config::SYNC_LAST_UPDATE_OPTIN_CONTACT_EXPORT) {
                        $this->updateLastModifiedContacts($website->getWebsiteId());
                        $this->exportContacts();
                        $noWebsiteQueueCollection = Mage::getModel('emarsys_suite2/queue')->getCollection()->addFieldToFilter('website_id',0);
                        foreach ($noWebsiteQueueCollection as $queueEntities){
                            $queueEntities->delete();
                        }
                    }
                    // if export before import selected, simply export everything
                    if ($config->getContactsSyncOrder() == Emarsys_Suite2_Model_Config::SYNC_DAILY_EXPORT_TO_IMPORT) {
                        $this->exportContacts();
                    }

                    if (!array_key_exists($config->getSettingsApiUsername(), $queue)) {
                        $queue[$config->getSettingsApiUsername()] = array();
                    }

                    $queue[$config->getSettingsApiUsername()][] = $website->getId();
                }
            } catch (Exception $e) {
            }
        }
        if ($config->getContactsSyncOrder() != Emarsys_Suite2_Model_Config::SYNC_LAST_UPDATE_OPTIN_CONTACT_EXPORT) {
            foreach ($queue as $websiteIds) {
                Mage::getSingleton('emarsys_suite2/api_subscriber')->requestSubscriptionUpdates($websiteIds);
            }
        }
    }

    /**
     * Cron method used to sync optin status from Emarsys to Magento [Frequency: Once In A Day may be 12:05 AM]
     */
    public function syncContactsSubscriptionData()
    {
        $queue = array();
        foreach (Mage::app()->getWebsites() as $website) {
            try {
                $config = Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
                /* @var $config Emarsys_Suite2_Model_Config */
                if ($this->_isEnabled()) {
                    if ($config->getContactsSyncOrder() == Emarsys_Suite2_Model_Config::SYNC_LAST_UPDATE_OPTIN_CONTACT_EXPORT) {
                        if (!array_key_exists($config->getSettingsApiUsername(), $queue)) {
                            $queue[$config->getSettingsApiUsername()] = array();
                        }

                        $queue[$config->getSettingsApiUsername()][] = $website->getId();
                    }
                }
            } catch (Exception $e) {

            }
        }

        if(count($queue) > 0) {
            foreach ($queue as $websiteIds) {
                Mage::getSingleton('emarsys_suite2/api_subscriber')->requestSubscriptionUpdates($websiteIds, true);
            }
        }
    }

    /**
     * Called when subscriber or customer deleted
     * 
     * @param Varien_Event_Observer $observer
     */
    public function customerDeleteAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        $website = $customer->getWebsiteId();
        if($website){
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
        }
        if (!$this->_isEnabled()) {
            return $this;
        }
        
        $customer = $observer->getCustomer();
        $subscriber = Mage::getModel('newsletter/subscriber')->load($customer->getId(), 'customer_id');
        if ($subscriber->getId()) {
            return $this;
//            $this->_addSubscriberDataToCustomer($customer, $subscriber);
//            $customer->setData('is_subscribed', 0);
            
        } else {
            Mage::getModel('emarsys_suite2/api_customer')->exportOne($customer, array('is_subscribed' => null));
        }
    }
    
    /**
     * Called when subscriber or customer deleted
     * 
     * @param Varien_Event_Observer $observer
     */
    public function subscriberDeleteAfter(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        $website = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();
        if($website){
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
        }
        if (!$this->_isEnabled()) {
            return $this;
        }
        
        $subscriber = $observer->getSubscriber();
        if ($subscriber->getEmarsysNoObserve()) {
            return;
        }

        $extraParams = array('is_subscribed' => null);
        if ($customerId = $subscriber->getCustomerId()) {
            // Don't set to false if customer still exists //
            if (Mage::getModel('customer/customer')->load($customerId, array('entity_id'))->getId()) {
                $extraParams = array('is_subscribed' => 0);;
            }
        }

        $customer = Mage::getModel('customer/customer')->load($subscriber->getCustomerId());
        if ($customer->getId()) {
            $this->_addSubscriberDataToCustomer($customer, $subscriber);
            $apiName = 'emarsys_suite2/api_customer';
            $dataObject = $customer;
        } else {
            $apiName = 'emarsys_suite2/api_subscriber';
            $dataObject = $subscriber;
        }

        // Delete should be realtime //
//        if (Mage::getSingleton('emarsys_suite2/config')->getSyncMode() == 'realtime') {
            Mage::getModel($apiName)->exportOne($dataObject, $extraParams);
//        } else {
//            Mage::getSingleton('emarsys_suite2/queue')->addEntity($dataObject, $extraParams);
//        }
    }
    
    public function pingAPI()
    {
        if (!$this->_isEnabled()) {
            return false;
        }

        $pingedApis = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $config = Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
            /* @var $config Emarsys_Suite2_Model_Config */
            $client = Mage::helper('emarsys_suite2')->getClient();
            /* @var $client Emarsys_Suite2_Model_Api */
            if (!isset($pingedApis[$config->getSettingsApiUsername()])) {
                $pingedApis[$config->getSettingsApiUsername()] = $client->ping();
            }
        }
        
        foreach ($pingedApis as $apiUser => $apiError) {
            if ($apiError != 1) {
                Mage::log(sprintf('API service ping error "%s" on API user "%s".', $apiError, $apiUser), LOG_CRIT, 'emarsys.log', true);
            }
        }
    }
    
    /**
     * Enables profiler if debug mode is set
     */
    public function enableProfiler(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('emarsys_suite2/settings/profiler')) {
            Varien_Profiler::enable();
        }
    }
    
    /**
     * Logs profiler if debug mode is set
     */
    public function logProfiler(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('emarsys_suite2/settings/profiler'))
        {
            foreach (Varien_Profiler::getTimers() as $code => $timer) {
                if (strpos($code, "EmarsysSuite2::") === 0) {
                    Mage::log(
                        sprintf(
                            '%s: called %s times for %01.4fs',
                            $code,
                            $timer['count'],
                            $timer['sum']
                        ),
                        LOG_DEBUG,
                        'emarsys-profiler.log',
                        true
                    );
                    Mage::log(
                        sprintf(
                            '%s: realmem=%s, emalloc=%s, realmem_start=%s, emalloc_start=%s',
                            $code,
                            $timer['realmem'],
                            $timer['emalloc'],
                            $timer['realmem_start'],
                            $timer['emalloc_start']
                        ),
                        LOG_DEBUG,
                        'emarsys-profiler.log',
                        true
                    );
                }
            }
        }
    }

    /**
    * Conditionnal Rewrite  Mage_Core_Model_Email_Template if
    * Store Configuration node  'emarsys_suite2_transmail/settings/enabled' is yes
    *
    * @param Varien_Event_Observer $observer
    */
    public function rewriteCoreEmailTemplate(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('emarsys_suite2_transmail/settings/enabled')) {
            Mage::getConfig()->setNode(
                'global/models/core/rewrite/email_template',
                'Emarsys_Suite2_Model_Email_Template'
            );
        }
    }
    
    }
