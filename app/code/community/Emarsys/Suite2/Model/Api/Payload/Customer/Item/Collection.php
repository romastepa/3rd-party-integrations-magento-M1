<?php
/**
 * API Customer Item collection
 *
 * Created to generate correct toArray based on mapping
 */
class Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection extends Varien_Data_Collection
{
    const EMARSYS_CREATED_FLAG = '_exists_in_suite';
    const EMARSYS_SUBSCRIBER_UPDATE_FLAG = '_update_ex_subscriber';
    const EMARSYS_MAIL_CHANGE_FROM = '_mail_changed_from';
    
    protected $_itemFactoryName = 'emarsys_suite2/api_payload_customer_item';

    protected $_hasMailChanges = false;
    protected $_ids = array();
    protected $_idsUpdate = array();
    protected $_idsCreate = array();
    protected $_emailsClean = array();
    protected $_idsUpdateExistingSubscriber = array();
    
    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->_ids = array();
        $this->_idsUpdate = array();
        $this->_idsCreate = array();
        $this->_emailsClean = array();
        $this->_idsUpdateExistingSubscriber = array();
        parent::clear();
    }
    
    /**
     * Returns key identifier
     *
     * @return string
     */
    protected function _getKeyId()
    {
        return $this->_getConfig()->getEmarsysCustomerKeyId();
    }

    /**
     * Returns config object
     *
     * @return Emarsys_Suite2_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('emarsys_suite2/config');
    }

    /**
     * @inheritdoc
     */
    public function addItem(Varien_Object $item)
    {
        if (!($item instanceof Emarsys_Suite2_Model_Api_Customer_Item)) {
            $item = Mage::getModel($this->_itemFactoryName, $item);
        }

        // These ids must go to separate array to filter them out in future //
        if ($item->getDataObject() && $item->getDataObject()->getData(self::EMARSYS_SUBSCRIBER_UPDATE_FLAG)) {
            $this->_idsUpdateExistingSubscriber[] = $item->getId();
        }

        if ($item->getDataObject() && $item->getDataObject()->getData(self::EMARSYS_MAIL_CHANGE_FROM)) {
            $this->_emailsClean[] = $item->getDataObject()->getData(self::EMARSYS_MAIL_CHANGE_FROM);
        }

        $this->_ids[] = $item->getId();
        return parent::addItem($item);
    }

    /**
     * Adds collection by item
     *
     * @param Varien_Data_Collection                         $collection Collection
     * @param Emarsys_Suite2_Model_Resource_Queue_Collection $queue      Queue if needed
     */
    public function addCollection($collection, $queue = null)
    {
        $this->clear();
        foreach ($collection as $item) {
            if ($queue && ($queueItem = $queue->getItemByEntityId($item->getId()))) {
                if ($params = $queueItem->getParams()) {
                    $params = unserialize($queueItem->getParams());
                    $item->addData($params);
                }
            }

            $this->addItem($item);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toArray($arrRequiredFields = array())
    {
        $arrItems = array();
        $arrItems['key_id'] = $this->_getKeyId();
        $arrItems['contacts'] = array();
        foreach ($this as $item) {
            $arrItems['contacts'][] = $item->toArray();
        }

        return $arrItems;
    }
    
    protected function _checkIds($keyId, $ids)
    {
        Mage::log(Varien_Debug::backtrace(1), 1, 1, 1, 1);
        $config = Mage::getSingleton('emarsys_suite2/config');
        $client = Mage::helper('emarsys_suite2')->getClient();
        return $client->post(
            'contact/checkids',
            array(
                'key_id' => $keyId,
                'external_ids' => $ids
            )
        );
    }

    /**
     * Delete  old emails when Customer changes his email.
     */
    public function cleanOldEMails()
    {
        $config = Mage::getSingleton('emarsys_suite2/config');
        $client = Mage::helper('emarsys_suite2')->getClient();
        
        $items = $itemsToDelete = array();
        if ($this->_emailsClean) {
            $response = $this->_checkIds($config->getEmarsysEmailKeyId(), $this->_emailsClean);
            foreach ($response['data']['ids'] as $email => $internalId) {
                $payload = array(
                    'key_id' => $config->getEmarsysEmailKeyId(),
                    $config->getEmarsysEmailKeyId() => $email
                );
                $client->post('contact/delete', $payload);
            }
        }

        return $this;
    }


    /**
     * Checks existing emails in suite.
     */
    public function callCheckEmailIds()
    {
        $config = Mage::getSingleton('emarsys_suite2/config');
        $items = array();
        foreach ($this->_items as $item) {
            $items[] = $item->getEmail();
            $item->setData(self::EMARSYS_CREATED_FLAG, false);
        };
        $response = $this->_checkIds($config->getEmarsysEmailKeyId(), $items);
        foreach ($response['data']['ids'] as $email => $internalId) {
            // add id to array of updates
            $item = $this->getItemByColumnValue('email', $email);
            if ($item) {
                $this->_idsUpdate[$email] = $item
                        ->setData(self::EMARSYS_CREATED_FLAG, true)
                        ->getDataObject()
                        ->getId();
            }
        }

        return $this;
    }

    /**
     * Returns Emails that have to update
     *
     * @return array|null
     */
    public function getEmailPayload()
    {
        $arrItems = array();
        $arrItems['key_id'] = $this->_getConfig()->getEmarsysEmailKeyId();
        $arrItems['contacts'] = array();
        $this->_ids = array();
        foreach ($this as $item) {
            $this->_ids[$item->getDataObject()->getId()] = $item->getDataObject()->getEmail();
            $arrItems['contacts'][] = $item->toArray();
        };
        if (empty($arrItems['contacts'])) {
            return null;
        }

        return $arrItems;
    }

    public function getIds()
    {
        return $this->_ids;
    }
    
    public function getExistingSubscriberIds()
    {
        return $this->_idsUpdateExistingSubscriber;
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
        foreach ($this as $item) {
            if (!$item->isSubscriberExists()) {
                $arrItems['contacts'][] = $item->toArray();
            }
        };
        if (empty($arrItems['contacts'])) {
            return null;
        }

        return $arrItems;
    }

    public function getExistingPayload()
    {
        $arrItems = array();
        $arrItems['key_id'] = $this->_getConfig()->getEmarsysSubscriberKeyId();
        $arrItems['contacts'] = array();
        foreach ($this as $item) {
            if ($item->isSubscriberExists()) {
                $this->_idsUpdateExistingSubscriber[] = $item->getDataObject()->getSubscriberId();
                $arrItems['contacts'][] = $item->toArray();
            }
        };
        if (empty($arrItems['contacts'])) {
            return null;
        }

        return $arrItems;
    }
    
    public function hasMailChanges()
    {
        return !empty($this->_emailsClean);
    }
}
