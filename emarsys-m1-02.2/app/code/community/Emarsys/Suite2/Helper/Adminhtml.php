<?php
class Emarsys_Suite2_Helper_Adminhtml extends Mage_Core_Helper_Abstract
{
    const BATCH_SIZE = 1000;
    protected $_allowedCronjobKeys = array('customers', 'subscribers', 'orders', 'contacts');
    protected $_logFiles = array(
        'emarsys.log',
        'emarsys-profiler.log'
    );
    protected $_mappingIgnoreFrontendInputTypes = array('gallery', 'image', 'file', 'media_image');
    protected $_mappingIgnoreAttributes = array('entity_id', 'password_hash', 'default_shipping', 'default_billing', 'disable_auto_group_change', 'reward_update_notification', 'reward_warning_notification', 'rp_token', 'rp_token_created_at');
    protected $_mappingIgnoreAddressAttributes = array('region_id');
    
    protected $_ignoredEmarsysApplicationTypes = array('interests', 'special', 'url');
    protected $_ignoredEmarsysNames = array('Magento Customer ID', 'Magento Subscriber ID', 'externalId');
    protected $_keyFields = array('key_id' => 'Magento Customer ID', 'subscriber_key_id' => 'Magento Subscriber ID', 'optin_id' => 'Opt-In', 'email' => 'Email');
    protected $_customFields = array(
        'c_last_login'           => 'Last logged in',
        'c_lifetime_order_total'  => 'Lifetime order total',
        'c_avg_sales'             => 'Average sales',
        'c_num_orders'            => 'Number of orders',
        'c_last_order_time'       => 'Last purchase time',
        'c_last_order_total'      => 'Last order total',
//        'c_last_purchased_product_1' => 'Last purchased product 1',
//        'c_last_purchased_product_2' => 'Last purchased product 2',
//        'c_last_purchased_product_3' => 'Last purchased product 3',
//        'c_last_purchased_product_4' => 'Last purchased product 4',
//        'c_last_purchased_product_5' => 'Last purchased product 5',
//        'c_last_cart_product_1'   => 'Last added to cart product 1',
//        'c_last_cart_product_2'   => 'Last added to cart product 2',
//        'c_last_cart_product_3'   => 'Last added to cart product 3',
//        'c_last_cart_product_4'   => 'Last added to cart product 4',
//        'c_last_cart_product_5'   => 'Last added to cart product 5'
    );
    
    /**
     * Updates core settings for the module
     * 
     * @return string
     */
    public function updateCoreSettings()
    {
        $result = $allFields = array();
        foreach (Mage::app()->getWebsites() as $website) {
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
            $url = Mage::helper('emarsys_suite2')->getAPIUrl();
            $username = Mage::getSingleton('emarsys_suite2/config')->setWebsite($website)->getSettingsApiUsername();
            $password = Mage::getSingleton('emarsys_suite2/config')->setWebsite($website)->getSettingsApiPassword();
            $client = Mage::getModel('emarsys_suite2/api', array('api_url' => $url, 'api_username' => $username, 'api_password' => $password));
            try {
                $response = $client->get('field/translate/en');
            } catch (Exception $e) {
                continue;
            }

            $keyFields = $this->_keyFields;
            if (isset($response['data'])) {
                foreach ($response['data'] as $item) {
                    if ($key = array_search($item['name'], $this->_keyFields)) {
                        unset($keyFields[$key]);
                        Mage::getConfig()->saveConfig('emarsys_suite2_contacts_sync/field_mapping/' . $key, $item['id'], 'websites', $website->getId());
                        // save optin choices //
                        if ($key == 'optin_id') {
                            if (($optInField = $client->get('field/' . $item['id'] . '/choice/translate/en')) && (!$optInField['replyCode'])) {
                                foreach ($optInField['data'] as $_optInChoice) {
                                    Mage::getConfig()->saveConfig('emarsys_suite2_contacts_sync/field_mapping/optin_' . strtolower($_optInChoice['choice']), $_optInChoice['id'], 'websites', $website->getId());
                                }
                            }
                        }
                    }
                }

                $allFields[$website->getName()] = $keyFields;
            }
        }

        foreach ($allFields as $key => $values) {
            if ($values) {
                $result[] = $key . ': ' . implode(', ', $values);
            }
        }

        return $result;
    }
    /**
     * Adds address attributes to list of magento fields
     * 
     * @param array  $array          Array to fill with fields
     * @param string $prefix         Prefix
     * @param string $frontendPrefix Label prefix
     */
    protected function _addAddressAttributes(&$array, $prefix, $frontendPrefix)
    {
        foreach (Mage::getModel('customer/address')->getAttributes() as $key => $attribute) {
            $attr = Mage::getSingleton('eav/config')->getAttribute('customer', $key);
            if (!is_object($attr)) {
                continue;
            }
            
            if (in_array($attribute->getFrontendInput(), $this->_mappingIgnoreFrontendInputTypes)) {
                continue;
            }
            
            if (in_array($attribute->getAttributeCode(), $this->_mappingIgnoreAddressAttributes)) {
                continue;
            }

            if ($attribute->getFrontendLabel()) {
                $array[$prefix . $key] = $frontendPrefix . $attribute->getFrontendLabel();
            }
        }
    }
    
    /**
     * Returns Magento fields list
     * 
     * @return array
     */
    public function getMappingMagentoFields()
    {
        $result = array('website_code' => 'Website Code', 'store_code' => 'Store Code');
        foreach (Mage::getModel('customer/customer')->getAttributes() as $key => $attribute) {
            $attr = Mage::getSingleton('eav/config')->getAttribute('customer', $key);
            if (((!is_object($attr))) || (!$attr->getAttributeId())) {
                continue;
            }
            
            if (in_array($key, $this->_mappingIgnoreAttributes)) {
                continue;
            }

            if (in_array($attribute->getData('frontend_input'), $this->_mappingIgnoreFrontendInputTypes)) {
                continue;
            }

            if ($attribute->getFrontendLabel()) {
                $result[$key] = $attribute->getFrontendLabel();
            } else {
                $result[$key] = $key;
            }
        }

        $this->_addAddressAttributes($result, 'default_billing_', 'Default Billing Address: ');
        $this->_addAddressAttributes($result, 'default_shipping_', 'Default Shipping Address: ');
        $result += $this->_customFields;
        return $result;
    }
    
    /**
     * Returns Emarsys fields list
     * 
     * @return array
     */
    public function getMappingEmarsysFields()
    {
        $website = Mage::getSingleton('adminhtml/config_data')->getWebsite();
        if ($website) {
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
        } else {
            Mage::getSingleton('emarsys_suite2/config')->setWebsite(0);
        }

        $client = Mage::helper('emarsys_suite2')->getClient();
        $result = array();
        try {
            $response = $client->get('field/translate/en');
            if (isset($response['data'])) {
                foreach ($response['data'] as $item) {
                    if (!in_array($item['name'], $this->_ignoredEmarsysNames)
                      && !in_array($item['application_type'], $this->_ignoredEmarsysApplicationTypes)) {
                        $result[$item['id']] = $item['name'];
                    }
                }
            }
        } catch (Exception $e) {
        }

        return $result;
    }
    
    /**
     * Checks if event is registered somewhere in backend and returns either empty array or path list
     * 
     * @param int $eventId
     * 
     * @return array
     */
    public function isEventRegistered($eventId)
    {
        $event = Mage::getModel('emarsys_suite2/email_event')->loadByEventId($eventId);
        if ($event->getId() && ($template = Mage::getModel('adminhtml/email_template')->load($event->getTemplateId())) && $template->getId()) {
            Mage::register('current_email_template', $template);
            $result = $this->_renderPaths(Mage::getBlockSingleton('adminhtml/system_email_template_edit')->getUsedCurrentlyForPaths(false));
            Mage::unregister('current_email_template');
        } else {
            $result = array();
        }

        return $result;
    }
    
    /**
     * Renders paths
     * 
     * @param array $events
     * 
     * @return array
     */
    protected function _renderPaths($events)
    {
        $results = array();
        foreach ($events as $event) {
            $result = array();
            foreach ($event as $path) {
                $line = $path['title'];
                if (isset($path['scope'])) {
                    $line .= ' [GLOBAL]';
                }

                if (isset($path['url'])) {
                    $line = '<a href="' . $path['url']. '">' . $line . '</a>';
                }

                $result[] = $line;
            }

            $results[] = implode(' > ', $result);
        }

        return $results;
    }
    
    public function getQueueStats()
    {
        $collection = Mage::getResourceModel('emarsys_suite2/queue_collection');
        $select = $collection->getSelect();
        /* @var $select Varien_Db_Select */
        $select->joinLeft(array('et' => $collection->getTable('eav/entity_type')), 'main_table.entity_type_id = et.entity_type_id', array('entity_type_code'));
        $select->joinLeft(array('ws' => $collection->getTable('core/website')), 'main_table.website_id = ws.website_id', array('website_name' => 'name'));
        $select->columns(array('items_queued' => new Zend_Db_Expr('COUNT(main_table.entity_type_id)')));
        $select->group(array('main_table.entity_type_id', 'website_id'));
        $select->order(array('main_table.website_id ASC', 'main_table.entity_type_id ASC'));
        
        $result = array();
        foreach ($collection as $item) {
            if (!isset($result[$item->getWebsiteName()])) {
                $result[$item->getWebsiteName()] = array();
            }

            $row = array(
                'entity_type'   => $item->getEntityTypeCode(),
                'count'         => $item->getItemsQueued(),
                'website'       => $item->getWebsiteName(),
                'website_id'    => $item->getWebsiteId()
            );
            if (!$row['entity_type']) {
                if ($item->getEntityTypeId() == Emarsys_Suite2_Model_Queue::ENTITY_TYPE_SUBSCRIBER) {
                    $row['entity_type'] = 'subscriber';
                }
            }

            $result[$item->getWebsiteName()][] = $row;
        }

        return $result;
    }
    
    public function getTrackedLogFiles()
    {
        return $this->_logFiles;
    }
    
    public function getBatchSize()
    {
        return self::BATCH_SIZE;
    }
    
    /**
     * Schedules cronjob into db table
     * 
     * @param string $which
     * @return boolean
     */
    public function scheduleCronjob($which)
    {
        if (!in_array($which, $this->_allowedCronjobKeys)) {
            return false;
        }

        $jobCode = 'emarsys_suite2_cron_export_' . $which;
        try {
            $cron = Mage::getResourceModel('cron/schedule_collection')
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->getFirstItem();
        } catch (Exception $e) {
            $cron = Mage::getModel('cron/schedule');
        }

        $result = $cron->setJobCode($jobCode)
             ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
             ->setCronExpr('* * * * *')
             ->trySchedule(Mage::getModel('core/date')->gmtTimestamp());
        $cron->save();
        return $result;
    }
}