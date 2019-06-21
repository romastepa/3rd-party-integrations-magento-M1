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
    protected $_ids = [];
    protected $_idsUpdate = [];
    protected $_idsCreate = [];
    protected $_emailsClean = [];
    protected $_idsUpdateExistingSubscriber = [];

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->_ids = [];
        $this->_idsUpdate = [];
        $this->_idsCreate = [];
        $this->_emailsClean = [];
        $this->_idsUpdateExistingSubscriber = [];
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
     * @param Varien_Object $item
     * @return $this|Varien_Data_Collection
     * @throws Exception
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

        try {
            if (!isset($this->_items[$item->getId()]) && $item->getId()) {
                $this->_ids[] = $item->getId();
                parent::addItem($item);
            } else {
                Mage::helper('emarsys_suite2')->log(Varien_Debug::backtrace(true, false), $this);
            }
        } catch (Exception $e) {
            throw new Exception('Item ('.get_class($item).') with the same id "'.$item->getId().'" already exist');
        }
        return $this;
    }

    /**
     * Adds collection by item
     *
     * @param Varien_Data_Collection $collection Collection
     * @param Emarsys_Suite2_Model_Resource_Queue_Collection|null $queue
     * @return Emarsys_Suite2_Model_Api_Payload_Customer_Item_Collection
     * @throws Exception
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
     * @param array $arrRequiredFields
     * @return array
     */
    public function toArray($arrRequiredFields = [])
    {
        $arrItems = [];
        $arrItems['key_id'] = $this->_getKeyId();
        $arrItems['contacts'] = [];
        foreach ($this as $item) {
            $arrItems['contacts'][] = $item->toArray();
        }

        return $arrItems;
    }

    /**
     * @param $keyId
     * @param $ids
     * @return mixed
     */
    protected function _checkIds($keyId, $ids)
    {
        Mage::log(Varien_Debug::backtrace(1), 1, 1, 1, 1);
        $client = Mage::helper('emarsys_suite2')->getClient();
        return $client->post(
            'contact/checkids',
            [
                'key_id' => $keyId,
                'external_ids' => $ids,
            ]
        );
    }

    /**
     * Delete  old emails when Customer changes his email.
     */
    public function cleanOldEMails()
    {
        $config = Mage::getSingleton('emarsys_suite2/config');
        $client = Mage::helper('emarsys_suite2')->getClient();

        if ($this->_emailsClean) {
            $response = $this->_checkIds($config->getEmarsysEmailKeyId(), $this->_emailsClean);
            foreach ($response['data']['ids'] as $email => $internalId) {
                $payload = [
                    'key_id' => $config->getEmarsysEmailKeyId(),
                    $config->getEmarsysEmailKeyId() => $email,
                ];
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
        $items = [];
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
        $arrItems = [];
        $arrItems['key_id'] = $this->_getConfig()->getEmarsysEmailKeyId();
        $arrItems['contacts'] = [];
        $this->_ids = [];
        foreach ($this as $item) {
            $this->_ids[$item->getDataObject()->getId()] = $item->getDataObject()->getEmail();
            $arrItems['contacts'][] = $item->toArray();
        }
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
        $arrItems = [];
        $arrItems['key_id'] = $this->_getKeyId();
        $arrItems['contacts'] = [];
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
        $arrItems = [];
        $arrItems['key_id'] = $this->_getConfig()->getEmarsysSubscriberKeyId();
        $arrItems['contacts'] = [];
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
