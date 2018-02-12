<?php
/**
 * API Customer Item
 * 
 * Created to generate correct toArray based on mapping
 */
class Emarsys_Suite2_Model_Api_Payload_Customer_Item extends Emarsys_Suite2_Model_Api_Payload_Abstract
{
    protected $_idFieldName = 'customer_id';
    protected $_addresses = array();
    
    protected function _getKeyId()
    {
        return $this->_getConfig()->getEmarsysCustomerKeyId();
    }
    
    /**
     * @inheritdoc
     */
    public function __construct(Mage_Customer_Model_Customer $customer)
    {
        /* @var $customer Mage_Customer_Model_Customer */
        $this->_getConfig()->setWebsite($customer->getWebsiteId());
        $mapping = $this->_getConfig()->getMapping();
        if(isset($mapping['is_subscribed'])) {
            unset($mapping['is_subscribed']);
        }
        $mappedCountries = Mage::helper('emarsys_suite2/country')->getMapping();
        $data['customer_id'] = $customer->getId();
        
        if ($customer->hasData('is_subscribed')) {
            $data['is_subscribed'] = $customer->getIsSubscribed();
        }

        if ($customer->getSubscriberId()) {
            $data['subscriber_id'] = $customer->getSubscriberId();
        }

        foreach ($mapping as $attributeCode => $emarsysFieldId) {
            $value = null;
            if (strpos($attributeCode, 'default_billing_') === 0) {
                $addressAttributeCode = str_replace('default_billing_', '', $attributeCode);
                if ($address = $this->_getAddress($customer, 'default_billing')) {
                    $value = $address->getData($addressAttributeCode);
                    if ($addressAttributeCode == 'country_id') {
                        $value = (isset($mappedCountries[$value]) ? $mappedCountries[$value] : $value);
                    }
                }
            } elseif (strpos($attributeCode, 'default_shipping_') === 0) {
                $addressAttributeCode = str_replace('default_shipping_', '', $attributeCode);
                if ($address = $this->_getAddress($customer, 'default_shipping')) {
                    $value = $address->getData($addressAttributeCode);
                    if ($addressAttributeCode == 'country_id') {
                        $value = (isset($mappedCountries[$value]) ? $mappedCountries[$value] : $value);
                    }
                }
            } else {
                $value = $customer->getData($attributeCode);
            }

            if (!is_null($value)) {
                if ($attribute = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode)) {
                    if ($attribute->getFrontendInput() == 'date') {
                        $value = $this->_formatDate($value);
                    }
                    else if ($attribute->getFrontendInput() == 'datetime') {
                        $value = $this->_formatDatetime($value);
                    }
                }

                $data[$attributeCode] = $value;
            }
        }

        if ($customer->isConfirmationRequired()) {
            $data['confirmation'] = (is_null($customer->getConfirmation()) ? 'Yes' : 'No');
        }

        $data['data_object'] = $customer;
        $data['created_at'] = $this->_formatDatetime($customer->getCreatedAtTimestamp());
        return parent::__construct($data);
    }
    
    protected function _getAddress($customer, $type)
    {
        if (($type == 'default_billing') && ($address = $customer->getDefaultBillingAddress())) {
            return $address;
        } else if (($type == 'default_shipping') && ($address = $customer->getDefaultShippingAddress())) {
            return $address;
        } else {
            // When customer was created in backend - this must be done to load address correctly.
            $addressId = $customer->getData($type);
            if ($addressId && !isset($this->_addresses[$addressId])) {
                $address = Mage::getModel('customer/address')->load($addressId);
                if (!$address->getId()) {
                    $address = null;
                }

                $this->_addresses[$addressId] = $address;
            }

            return (isset($this->_addresses[$addressId]) ? $this->_addresses[$addressId] : null);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(array $arrAttributes = array())
    {
        
        $mapping = $this->_getConfig()->getMapping();
        if(isset($mapping['is_subscribed'])) {
            unset($mapping['is_subscribed']);
        }
        $result[$this->_getKeyId()] = $this->getId();
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
    
    public function isSubscriberExists()
    {
        return ($this->getDataObject() && $this->getDataObject()->getData(Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection::EMARSYS_SUBSCRIBER_UPDATE_FLAG));
    }
}
