<?php

class Emarsys_Suite2_Model_Api_Customer extends Emarsys_Suite2_Model_Api_Abstract
{
    protected function _getKeyId()
    {
        return $this->_getConfig()->getEmarsysCustomerKeyId();
    }

    protected $_profilerKey = 'customer';
    
    /**
     * @inheritdoc
     */
    public function isExportEnabled()
    {
        return $this->_getConfig()->isCustomersExportEnabled();
    }
    
    /**
     * Returns entity
     * 
     * @return type Mage_Customer_Model_Customer
     */
    protected function _getEntity()
    {
        return Mage::getSingleton('customer/customer');
    }

    /**
     * Returns payload instance
     * 
     * @return Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection
     */
    protected function _getPayloadInstance()
    {
        return Mage::getModel('emarsys_suite2/api_payload_customer_item_collection');
    }
    
    /**
     * Unsets ids which are present in response as errorous records
     * 
     * @param array $response Response
     * @param array $ids      Ids array
     * 
     * @return Emarsys_Suite2_Model_Api_Customer
     */
    protected function _filterRecords($response, &$ids, &$errors)
    {
        if (isset($response['data']['errors'])) {
            foreach ($response['data']['errors'] as $id => $_errors) {
                // remove ids with errors //
                $errorIndex = array_search($id, $ids);
                foreach ($_errors as $errorId => $errorString) {
                    if (!isset($errors[$errorId]) || !is_array($errors[$errorId])) {
                        $errors[$errorId] = array();
                    }

                    $errors[$errorId][] = $id;
                }

                unset($ids[$errorIndex]);
            }
        }

        return $this;
    }
    
    /**
     * Returns customers collection
     * 
     * @param array $ids
     * 
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function _getCollection($ids)
    {
        $logEnabled = is_object(Mage::getModel('log/customer'));
        // We need to join subscriber id to get the id of subscriber on a customer record
        $collection = Mage::getResourceModel('customer/customer_collection')
                ->addAttributeToFilter('entity_id', array('in' => $ids))
                ->addAttributeToSelect(array_keys($this->_getConfig()->getMapping()))
                ->addAttributeToSelect(array('default_billing', 'default_shipping'))
                ->joinTable(array('ns' => 'newsletter/subscriber'), 'customer_id = entity_id', array('subscriber_id', 'is_subscribed' => new Zend_Db_Expr('IF(subscriber_status = 1, 1, 0)')), null, 'left');
        $salesData = Mage::helper('emarsys_suite2')->getCustomersOrdersData($ids);
        foreach ($collection as $item) {
            if (isset($salesData[$item->getEntityId()])) {
                $item->addData($salesData[$item->getEntityId()]);
            }

            if ($this->getIsFullExport() && $logEnabled) {
                $logCustomer = Mage::getModel('log/customer')->loadByCustomer($item->getId());
                if ($logCustomer->hasLastVisitAt()) {
                    $item->setData('c_last_login', $logCustomer->getLastVisitAt());
                }
            }
        }

        return $collection;
    }
    
    /**
     * Prepares payload data for exporting
     * 
     * @param Emarsys_Suite2_Model_Resource_Queue_Collection $queue
     * 
     * @return Emarsys_Suite2_Model_Api_Customer_Item_Collection
     */
    protected function _getPayload($queue)
    {
        $ids = $queue->getColumnValues('entity_id');
        if ($ids) {
            $collection = $this->_getCollection($ids);
            if ($collection->getSize()) {
                return $this->_getPayloadInstance()->addCollection($collection, $queue);
            }
        }

        return false;
    }
    
    /**
     *
     *
     * @inheritdoc
     */
    public function exportBunchData($queue)
    {
        if ($payload = $this->_getPayload($queue)) {
            $processedEntities = $this->_apiExportPayload($payload);
            $this->_processedEntities = array_merge($this->_processedEntities, $processedEntities);
        }
    }
    
    /**
     * Exports customers from website
     * 
     * @return Emarsys_Suite2_Model_Api_Customer
     */
    protected function _exportWebsiteData($website)
    {
        /* @var $config Emarsys_Suite2_Model_Config */
        if (!$this->isExportEnabled()) {
            return $this;
        }

        $queue = null;
        $queueInstance = Mage::getModel('emarsys_suite2/queue');
        if ($this->getCustomerIds() && is_array($this->getCustomerIds())) {
            $queueInstance->setEntityIds($this->getCustomerIds());
        }

        do {
            try {
                if ($queue = $queueInstance->getNextBunch($this->_getEntity(), $website->getId(), $queue)) {
                    $this->exportBunchData($queue);
                }
            } catch (Exception $e) {
                $this->log('Got exception when iterating batch: ' . $e->getMessage());
            }
        } while ($queue);
        return $this;
    }

    /**
     * Exports to API
     *
     * @param $payload
     * @param bool $updateExistingSubscribers
     * @return array
     */
    protected function _apiExportPayload($payload, $updateExistingSubscribers = false)
    {
        $errors = $data = array();
        if ($updateExistingSubscribers) {
            // no need to do anything when email is a key as there should be no conflict
            // since we initially removed duplicates in this case
            $data = array();
            $ids =  array();
        } else {
            $data = $payload->getEmailPayload();
            $ids  = $payload->getIds();
        }
        
        // Wipes out old emails from Suite if there are email changes, as updating these emails is not possible //
        if ($payload->hasMailChanges()) {
            $payload->cleanOldEMails();
        }

        if (!$updateExistingSubscribers && empty($data)) {
            return $this->_apiExportPayload($payload, 1);
        }

        // POST/PUT API call
        $profilerCode = 'ApiExportPayload_' . $this->_profilerKey;
        $this->_profilerStart($profilerCode);
        try {
            if ($data) {
                $response = $this->getClient()->put('contact/create_if_not_exists=1', $data);
                $this->_filterRecords($response, $ids, $errors);
                $ids = array_flip($ids); // flip back to email => id

                if (!$updateExistingSubscribers) {
                    $ids = array_merge($ids, $this->_apiExportPayload($payload, true));
                }
            }

            // clean successful entities
            $this->_profilerStop($profilerCode);
            if (!$updateExistingSubscribers) {
                $ids = array_merge($ids, $this->_apiExportPayload($payload, 1));
            }

            return $ids;
        } catch (Exception $e) {
            $this->_profilerStop($profilerCode);
            $this->log($e->getMessage());
        }

        return array();
    }
    
    public function checkEmailExists($email, $websiteId)
    {
        $config = Mage::getSingleton('emarsys_suite2/config');
        /* @var $config Emarsys_Suite2_Model_Config */
        $config->setWebsite(Mage::app()->getWebsite($websiteId));
        $client = Mage::helper('emarsys_suite2')->getClient();
        try {
            $response = $client->post(
                'contact/checkids',
                array(
                    'key_id' => $config->getEmarsysEmailKeyId(),
                    'external_ids' => array($email)
                )
            );
            if (isset($response['data']) && isset($response['data']['errors']) && isset($response['data']['errors'][$email])) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            
        }
    }
    /**
     * 
     * @param type $email
     * @param type $websiteId
     * @param type $method
     */
    public function exportEmail($email, $websiteId = 0, $customerId = null, $subscriberId = null)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $website = Mage::app()->getWebsite($websiteId);
        } else {
            $website = Mage::app()->getWebsite();
        }

        $this->_getConfig()->setWebsite($website);
        $data = array(
            'key_id' => $this->_getConfig()->getEmarsysEmailKeyId(),
            $this->_getConfig()->getEmarsysEmailKeyId() => $email,
        );
        if ($customerId) {
            $data[$this->_getConfig()->getEmarsysCustomerKeyId()] = $customerId;
        }

        if ($subscriberId) {
            $data[$this->_getConfig()->getEmarsysSubscriberKeyId()] = $subscriberId;
        }

        try {
            $this->getClient()->put('contact/create_if_not_exists=1', $data);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Exports single customer
     *
     * @param $object
     * @param array $extraData
     * @return boolean
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function exportOne($object, $extraData = array())
    {
        if (Mage::app()->getStore()->isAdmin()) {
            if ($object->getWebsiteId()) {
                $website = Mage::app()->getWebsite($object->getWebsiteId());
            } else {
                $website = Mage::app()->getStore($object->getStoreId())->getWebsite();
            }
        } else {
            if ($object->getStoreId()) {
                $website = Mage::app()->getStore($object->getStoreId())->getWebsite();
            } else {
                $website = Mage::app()->getWebsite();
            }
        }

        if ($website->getId() == 0) {
            $type = ($object instanceof Mage_Customer_Model_Customer) ? 'Customer' : 'Subscriber';
            $this->log(sprintf('%s ID %s has admin website assignment. Export will not be executed.', $type, $object->getId()));
            // Return true to avoid queueing
            return true;
        }

        $this->_getConfig()->setWebsite($website);

        try {
            if ($extraData) {
                $object->addData($extraData);
            }

            $payload = $this->_getPayloadInstance()->addItem($object);

            if ($this->_apiExportPayload($payload)) {
                Mage::getSingleton('emarsys_suite2/queue')->removeEntity($object);
            }
        } catch (Exception $e) {
            // Need to schedule exporting to Emarsys, only update this attribute
            Mage::getSingleton('emarsys_suite2/queue')->addEntity($object, $extraData);
            // Log $e now in file //
            $this->log($e->getMessage());
            return false;
        }

        return true;
    }
    
    /**
     * Forced export method
     */
    public function exportForced()
    {
        $this->setIsFullExport(true);
        $this->export();
    }
}