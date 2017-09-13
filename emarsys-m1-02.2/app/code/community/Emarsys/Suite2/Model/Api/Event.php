<?php

class Emarsys_Suite2_Model_Api_Event extends Emarsys_Suite2_Model_Api_Abstract
{
    /**
     * Returns list of events in Emarsys Suite
     * 
     * @return array
     */
    public function getEvents()
    {
        $result = array();
        $response = $this->getClient()->get('event');
        if (!empty($response['data'])) {
            foreach ($response['data'] as $item) {
                $result[$item['id']] = $item['name'];
            }
        }

        return $result;
    }
    
    /**
     * Triggers mail for customer
     * 
     * @param int                          $eventId
     * @param Mage_Customer_Model_Customer $customer
     * @param array                        $data
     */
    public function triggerEvent($eventId, $keyId, $externalId, $data, $websiteId = 0)
    {
        $config = Mage::getSingleton('emarsys_suite2/config');
        if (!$websiteId) {
            $config->setWebsite(Mage::app()->getWebsite());
        } else {
            $config->setWebsite(Mage::app()->getWebsite($websiteId));
        }

        /* @var $config Emarsys_Suite2_Model_Config */
        if (!$config->isEnabled()) {
            return false;
        }

        $data = array(
            'external_id' => $externalId,
            'key_id'      => $keyId,
            'data'        => $data
        );
        try {
            $response = $this->getClient()->post('event/' . $eventId . '/trigger', $data);
            return !$response['replyCode'];
        } catch (Exception $e) {
            $this->log($e->getMessage());
            return false;
        }
    }
}
