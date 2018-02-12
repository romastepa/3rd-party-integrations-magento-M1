<?php

class Emarsys_Suite2_Model_Api_Subscriber extends Emarsys_Suite2_Model_Api_Customer
{
    protected $_profilerKey = 'subscriber';
    
    protected function _getKeyId()
    {
        return $this->_getConfig()->getEmarsysSubscriberKeyId();
    }
    
    /**
     * @inheritdoc
     */
    public function isExportEnabled()
    {
        return $this->_getConfig()->isSubscribersExportEnabled();
    }
    
    /**
     * @inheritdoc
     */
    protected function _getEntity()
    {
        return Mage::getSingleton('newsletter/subscriber');
    }
    
    /**
     * @inheritdoc
     */
    protected function _getCollection($ids)
    {
        return Mage::getResourceModel('newsletter/subscriber_collection')->addFieldToFilter('subscriber_id', array('in' => $ids));
    }
    
    /**
     * @inheritdoc
     */
    protected function _getPayloadInstance()
    {
        return Mage::getModel('emarsys_suite2/api_payload_subscriber_item_collection');
    }
    
    /**
     * Processes subscription updates
     * 
     * @param string $contents Contents
     * 
     * @return boolean
     */
    protected function _processSubscriptionUpdates($contents, $isTimeBased = false)
    {
        if ($contents) {
            $csv = new Varien_Convert_Parser_Csv();
            $csv->setVar('fieldnames', true);
            $csv->setData($contents);
            $csv->parse();
            if (!$csv->getData()) {
                return false;
            }
            $subscriberIds = array();
            foreach ($csv->getData() as $row) {
                $id = $row['Magento Subscriber ID'];
                $optIn = (strtolower($row['Opt-In']) == 'true' ?
                            Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED :
                            Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                
                $model = Mage::getModel('newsletter/subscriber')->load($id);
                if ($id && $model->getId()) {
                    if($isTimeBased){
                        $subscriberIds[] = $model->getId();
                    } else {
                        $model->setSubscriberStatus($optIn)
                            ->setEmarsysNoObserve(true)
                            ->save();
                    }

                }
            }

            if (count($subscriberIds) > 0) {
                $websiteId = $this->_getConfig()->getWebsiteId();
                Mage::helper("emarsys_suite2/timeBasedOptinSync")->backgroudTimeBasedOptinSync($subscriberIds, $websiteId);
            }

            return true;
        }

        return false;
    }

    /**
     * Imports subscription updates
     */
    public function importSubscriptionUpdates(array $websiteIds, $isTimeBased = false)
    {
        $this->_getConfig()->setWebsite(Mage::app()->getWebsite(current($websiteIds)));
        $client = $this->getClient();
        if ($this->_isEnabled()) {
            $offset = 0;
            $limit = 100;
            do {
                $exportId = $this->_getConfig()->getValue('export_id');
                $apiCall = sprintf('export/%s/data/offset=%s&limit=%s', $exportId, $offset, $limit);
                $response = $client->get($apiCall, array(), false);
                $offset+=$limit;
            } while ($this->_processSubscriptionUpdates($response, $isTimeBased));
        }
    }
    
    /**
     * API Request to get updates
     */
    public function requestSubscriptionUpdates(array $websiteIds, $isTimeBased = false)
    {
        Mage::getSingleton('emarsys_suite2/config')->setWebsite(Mage::app()->getWebsite(current($websiteIds)));
        $client = $this->getClient();
        $dt = new Zend_Date();

        if($isTimeBased) {
            $timeRange = array($dt->subHour(1)->toString('YYYY-MM-dd'), $dt->addHour(1)->toString('YYYY-MM-dd'));
        } else {
            $timeRange = array($dt->subDay(1)->toString('YYYY-MM-dd'), $dt->addDay(1)->toString('YYYY-MM-dd'));
        }

        $payload = array(
            'distribution_method' => 'local',
            'origin'              => 'all',
            'origin_id'           => '0',
            'contact_fields'      => array($this->_getKeyId(), $this->_getConfig()->getEmarsysOptInFieldId()),
            'add_field_names_header' => 1,
            'time_range' => $timeRange,
            'notification_url'    => $this->_getConfig()->getExportsNotificationUrl($websiteIds, $isTimeBased)
        );
        try {
            $response = $client->post('contact/getchanges', $payload);
            if ($response['data']['id']) {
                $this->_getConfig()->setValue('export_id', $response['data']['id']);
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }
    }
    
    /**
     * @inheritdoc
     */
    protected function _afterApiExportPayload($payload)
    {
        return $this;
    }
    
    protected function _afterExportWebsiteData($website)
    {
        parent::_afterExportWebsiteData($website);
        if ($this->getIsFullExport()) {
            // In case of full export we must export customers as subscribers //
            $this->log('Checking if subscribers\' customer export needed.');
            $customerIds = Mage::getResourceModel('newsletter/subscriber_collection')->addFieldToFilter('customer_id', array('gt' => 0))->getColumnValues('customer_id');
            if ($customerIds) {
                $this->log('Starting subscribers\' customer export.');
                    // Export and send these customers to subscribers contactlist only //
                Mage::getSingleton('emarsys_suite2/api_customer')
                    ->setCustomerIds($customerIds)
                    ->exportForced();
            }
        }
    }
}