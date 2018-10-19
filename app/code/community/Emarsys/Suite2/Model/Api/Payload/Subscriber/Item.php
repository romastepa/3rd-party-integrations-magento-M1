<?php

class Emarsys_Suite2_Model_Api_Payload_Subscriber_Item extends Emarsys_Suite2_Model_Api_Payload_Customer_Item
{
    protected $_idFieldName = 'subscriber_id';
    
    protected function _getKeyId()
    {
        return $this->_getConfig()->getEmarsysSubscriberKeyId();
    }
    /**
     * @inheritdoc
     */
    public function __construct(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $data = array(
            'subscriber_id'     => $subscriber->getId(),
            //'firstname'         => '',
            //'lastname'          => '',
            'email'             => $subscriber->getSubscriberEmail(),
        );

        if ($subscriber->hasData('is_subscribed')) {
            if ($subscriber->getIsSubscribed() === null) {
                $flag = null;
            } else {
                $flag = ($subscriber->getIsSubscribed() ? $this->_getConfig()->getEmarsysOptInTrue() : $this->_getConfig()->getEmarsysOptInFalse());
            }
        } else {
            $flag = (($subscriber->getSubscriberStatus() == 1) ? $this->_getConfig()->getEmarsysOptInTrue() : $this->_getConfig()->getEmarsysOptInFalse());
        }

        $data['is_subscribed'] = $flag;
        $data['data_object'] = $subscriber;
        return Varien_Object::__construct($data);
    }

    /**
     * @inheritdoc
     */
    public function toArray(array $arrAttributes = array())
    {
        $mapping = $this->_getConfig()->getMapping();

        $result[$this->_getKeyId()] = $this->getId();
        $result[$this->_getConfig()->getEmarsysCustomerKeyId()] = '';
        if($this->getDataObject()->getCustomerId()){
            $result[$this->_getConfig()->getEmarsysCustomerKeyId()] = $this->getDataObject()->getCustomerId();
        }
        $data = $this->_data;

        // Add subscriber id to resulting array
        if ($this->getSubscriberId()) {
            $result[$this->_getConfig()->getEmarsysSubscriberKeyId()] = $this->getSubscriberId();
        }
        foreach ($mapping as $attributeCode => $emarsysId) {
            if (array_key_exists($attributeCode, $data)) {
                $result[$emarsysId] = $data[$attributeCode];
            }
        }
        if ($this->isSubscriberExists()) {
            $result[$this->_getConfig()->getEmarsysSubscriberKeyId()] = $this->getDataObject()->getSubscriberId();
        }
        // Add store code //
        $storeId = $this->getDataObject()->getStoreId()
            ? $this->getDataObject()->getStoreId()
            : Mage::app()->getWebsite($this->getDataObject()->getWebsiteId())->getDefaultStore()->getId();

        if (isset($mapping['store_code'])) {
            $result[$mapping['store_code']] = Mage::app()->getStore($storeId)->getCode();
        }
        // Add website code //
        if (isset($mapping['website_code'])) {
            $result[$mapping['website_code']] = Mage::app()->getStore($storeId)->getWebsite()->getCode();
        }

        return $result;
    }
}
