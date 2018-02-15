<?php

class Emarsys_Suite2_Helper_TimeBasedOptinSync extends Emarsys_Suite2_Helper_Data
{
    const OPTIN_PRIORITY = 'Emarsys';

    /**
     * @param $subscriber
     */
    public function realtimeTimeBasedOptinSync($subscriber)
    {
        try {
            $websiteId = 0;
            if ($subscriber->getStoreId()) {
                $website = Mage::app()->getStore($subscriber->getStoreId())->getWebsite();
                $websiteId = $website->getId();
            }

            if ($websiteId == 0) {
                $line = sprintf('Subscriber ID %s has admin website assignment. Export will not be executed.', $subscriber->getId());
                Mage::helper('emarsys_suite2')->log($line, $this);
                return true;
            }
            $emarsysTime = '';
            $emarsysDate = '';
            $scopeId = Mage::getModel('core/store')->load($subscriber->getStoreId())->getWebsiteId();
            $storeId = (is_object($scopeId) ?
                $websiteId->getDefaultStore()->getId() :
                Mage::app()->getWebsite($scopeId)->getDefaultStore()->getId()
            );
            $configkeyId = Mage::getStoreConfig('emarsys_suite2_contacts_sync/settings/email_as_id', $storeId);
            $fieldId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysOptInFieldId();
            if ($configkeyId) {
                $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysEmailKeyId();
                $keyValue = $subscriber->getSubscriberEmail();
            } else {
                $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysSubscriberKeyId();
                $keyValue = $subscriber->getSubscriberId();
            }
            $payload = array(
                'key_id' => $keyId,
                'key_value' => $keyValue,
                'field_id' => $fieldId
            );
            $response = Mage::getSingleton('emarsys_suite2/api_subscriber')->getClient()->get("contact/last_change", $payload);
            // print_r($response);exit;
            if (isset($response['data']['time'])) {

                $emarsysTime = $response['data']['time'];
                $EmarsysOptinChangeTime = $this->convertToUtc($emarsysTime);
                $magentoOptinChangeTime = $this->getSubscriberChangeStatusAt($subscriber->getId());
                if (isset($response['data']['current_value'])) {
                    $emarsysOptinValue = $response['data']['current_value'];
                }
                $magentoOptinValue = $subscriber->getSubscriberStatus();
                Mage::helper('emarsys_suite2')->log('Subscriber'.$subscriber->getId().' => Emarsys Optin Val: '.$emarsysOptinValue.' & Magento Optin Val: '.$magentoOptinValue);
                Mage::helper('emarsys_suite2')->log('Subscriber'.$subscriber->getId().' => Emarsys Last Update: '. $emarsysTime.'(Converted: '.$EmarsysOptinChangeTime.') & Magento Last Update: '.$magentoOptinChangeTime);
                if ((($EmarsysOptinChangeTime == $magentoOptinChangeTime && self::OPTIN_PRIORITY =='Emarsys') || ($EmarsysOptinChangeTime >= $magentoOptinChangeTime)) && $emarsysOptinValue != $magentoOptinValue) {
                    if($emarsysOptinValue ==1){
                        $statusToBeChanged = Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                    }elseif($emarsysOptinValue ==2){
                        $statusToBeChanged = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                    }else{
                        $statusToBeChanged = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                    }
                    $subscriber->setSubscriberStatus($statusToBeChanged)
                        ->setEmarsysNoExport(true)
                        ->save();
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Time based optin sync for backgourd type
     */
    public function backgroudTimeBasedOptinSync($subscriberIdsArray, $websiteId)
    {
        try {
            Mage::helper('emarsys_suite2')->log(print_r($subscriberIdsArray,true));
        
            $fieldId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysOptInFieldId();
            $subscribersCollection = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToFilter('subscriber_id',array('in'=>$subscriberIdsArray));
            $magLastModifiedStatus = array();

            $storeId = (is_object($websiteId) ?
                $websiteId->getDefaultStore()->getId() :
                Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId()
            );
            $configkeyId = Mage::getStoreConfig('emarsys_suite2_contacts_sync/settings/email_as_id', $storeId);
            if ($configkeyId) {
                $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysEmailKeyId();
                $keyValue = $subscribersCollection->getColumnValues('subscriber_email');
                foreach($subscribersCollection as $_subscriber){
                    $magLastModifiedStatus[$_subscriber->getSubscriberEmail()] =  array('change_status_at' => $this->getSubscriberChangeStatusAt($_subscriber->getId()), 'subscriber_status' => $_subscriber->getSubscriberStatus());
                }
            } else {
                $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysSubscriberKeyId();
                $keyValue = $subscriberIdsArray;
                foreach($subscribersCollection as $_subscriber){
                    $magLastModifiedStatus[$_subscriber->getSubscriberId()] =  array('change_status_at' => $this->getSubscriberChangeStatusAt($_subscriber->getId()), 'subscriber_status' => $_subscriber->getSubscriberStatus());
                }
            }

            $payload = array(
                'keyId' => $keyId,
                'keyValues' => $keyValue,
                'fieldId' => $fieldId
            );
            $response = Mage::getSingleton('emarsys_suite2/api_subscriber')->getClient()->post("contact/last_change", $payload);

            if (isset($response['data']['result'])) {
                $emarsysSubscribers = $response['data']['result'];
                //echo "sudheer";print_r($emarsysSubscribers);exit;
                foreach ($emarsysSubscribers as $emarsysSubscriberKey => $emarsysSubscriberValue) {
                    $magentoLastUpdatedTime = $magLastModifiedStatus[$emarsysSubscriberKey]['change_status_at'];
                    $magentoSubscriptionStatus = $magLastModifiedStatus[$emarsysSubscriberKey]['subscriber_status'];
                    $currentEmarsysSubcsriptionStatus = $emarsysSubscriberValue['current_value'];
                    $emarsysLastUpdateTime = $this->convertToUtc($emarsysSubscriberValue['time']);
                    Mage::helper('emarsys_suite2')->log('Subscriber: '.$emarsysSubscriberKey.' => Emarsys Optin Val: '.$currentEmarsysSubcsriptionStatus.' & Magento Optin Val: '.$magentoSubscriptionStatus);
                    Mage::helper('emarsys_suite2')->log('Subscriber: '.$emarsysSubscriberKey.' => Emarsys Last Update: '. $emarsysSubscriberValue['time'].'(Converted: '.$emarsysLastUpdateTime.') & Magento Last Update: '.$magentoLastUpdatedTime);
                    if ($currentEmarsysSubcsriptionStatus != $magentoSubscriptionStatus) {
                        if($emarsysLastUpdateTime > $magentoLastUpdatedTime || ($emarsysLastUpdateTime == $magentoLastUpdatedTime && self::OPTIN_PRIORITY =='Emarsys')){

                            if($currentEmarsysSubcsriptionStatus ==1){
                                $statusToBeChanged = Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                            }elseif($currentEmarsysSubcsriptionStatus ==2){
                                $statusToBeChanged = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                            }else{
                                $statusToBeChanged = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                            }

                            if ($configkeyId) {
                                $emarsysSubscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($emarsysSubscriberKey);
                            } else {
                                $emarsysSubscriber = Mage::getModel('newsletter/subscriber')->load($emarsysSubscriberKey);
                            }

                            $emarsysSubscriber->setSubscriberStatus($statusToBeChanged)
                                ->setEmarsysNoExport(true)
                                ->save();
                            Mage::helper('emarsys_suite2')->log($emarsysSubscriber->getSubscriberId() . " => Optin Updated to " . $statusToBeChanged);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage());
        }
    }


    public function convertToUtc($emarsysTime)
    {
        try {
            $emarsysDate = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $emarsysTime,
                new DateTimeZone('Europe/Vienna')
            );
            $acst_date = clone $emarsysDate; // we don't want PHP's default pass object by reference here
            $acst_date->setTimeZone(new DateTimeZone('UTC'));
            return $EmarsysOptinChangeTime = $acst_date->format('Y-m-d H:i:s');  // UTC:  2011-04-27 2:exit;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    public function getSubscriberChangeStatusAt($subscriberId)
    {
        try {
            if ($subscriberId) {
                $resource = Mage::getSingleton('core/resource');
                $read = $resource->getConnection('core_read');
                $timeStamp = 'SELECT change_status_at FROM ' . $resource->getTableName('newsletter_subscriber') . ' where subscriber_id = ' . $subscriberId;
                $dbTime = $read->fetchOne($timeStamp);
                if ($dbTime) {
                    return $dbTime;
                } else {
                    return;
                }
            }

        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
