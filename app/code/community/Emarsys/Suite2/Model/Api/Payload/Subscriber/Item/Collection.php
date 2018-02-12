<?php
/**
 * API Customer Item collection
 * 
 * Created to generate correct toArray based on mapping
 */
class Emarsys_Suite2_Model_Api_Payload_Subscriber_Item_Collection extends Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection
{
    protected $_itemFactoryName = 'emarsys_suite2/api_payload_subscriber_item';    
    
    /**
     * Returns key identifier
     * 
     * @return string
     */
    protected function _getKeyId()
    {
        return $this->_getConfig()->getEmarsysSubscriberKeyId();
    }

    /**
     * Returns payload for update
     *
     * @return array
     */
    public function getPayload()
    {
        $arrItems = array();
        $arrItems['key_id'] = $this->_getKeyId();
        $arrItems['contacts'] = array();
        $isNewCustomerSubscriber = false;
        foreach ($this as $item) {
            if (!$item->isSubscriberExists()) {
                $arrItems['contacts'][] = $item->toArray();
            }

            if($item->getDataObject()->isObjectNew() && $item->getDataObject()->getCustomerId()) {
                $isNewCustomerSubscriber = true;
            } else {
                $isNewCustomerSubscriber = false;
            }

        };
        if (empty($arrItems['contacts'])) {
            return null;
        }
        /* Use the KEY ID as customer ID for new registration with subscription to avoid creating two contact records in Emarsys */
        if($isNewCustomerSubscriber){
            $arrItems['key_id'] = $this->_getConfig()->getEmarsysCustomerKeyId();
        }

        return $arrItems;
    }
}
