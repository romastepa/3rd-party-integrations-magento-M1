<?php

class BackendTest extends PHPUnit_Framework_TestCase
{
    protected $_email = 'abcd@example.com';
    
    const STORE = 'admin';
    /**
     * Client API
     * 
     * @var Emarsys_Suite2_Model_Api
     */
    protected static $_client;
    
    /**
     * 
     * @return Emarsys_Suite2_Model_Debug
     */
    public function debug()
    {
        return Mage::getSingleton('emarsys_suite2/debug');
    }
    
    /**
     * Config
     * 
     * @var Emarsys_Suite2_Model_Config
     */
    protected static $_config;
    
    public static function setUpBeforeClass()
    {
        require '../../../../../Mage.php';
        Mage::app(static::STORE);
        if (!Mage::registry(Emarsys_Suite2_Model_Api::DEBUG_KEY)) {
            Mage::register(Emarsys_Suite2_Model_Api::DEBUG_KEY, 1);
        }

        static::$_config = Mage::getSingleton('emarsys_suite2/config')->setWebsite(Mage::app()->getDefaultStoreView()->getWebsiteId());
        static::$_client = Mage::helper('emarsys_suite2')->getClient();
        Mage::app()->getStore(Mage::app()->getDefaultStoreView()->getId())->setConfig('emarsys_suite2_contacts_sync/settings/mode', 'realtime');        
    }
    
    public function setUp()
    {
        Mage::log('[PHPUnit] Tests for ' . $this->getName(). ' started in ' . get_class($this), LOG_DEBUG, 'emarsys.log', true);
    }
    
    protected function _assertCallsAmount($call, $method, $amount, $message = '')
    {
        $info = $this->debug()->get();
        $this->assertArrayHasKey($call, $info, sprintf('%sNo API calls %s done', $message, $call));
        if (isset($info[$call])) {
            $this->assertArrayHasKey($method, $info[$call], sprintf('%sNo %s for API call %s done', $message, $method, $call));
            if (isset($info[$call][$method])) {
                $count = count($info[$call][$method]);
                $this->assertEquals($count, $amount, sprintf('%sAPI call %s(%s) expected: %s, done: %s', $message, $call, $method, $amount, $count));
            }
        }        
    }
    
    public function testPing()
    {
        $this->debug()->clear();
        $this->assertEquals(1, static::$_client->ping());
        $this->_assertCallsAmount('settings', 'GET', 1);
    }
    
//    public function testCustomerPayloadCreate()
//    {
//        $customer = Mage::getModel('customer/customer')->setData(
//            array(
//                'entity_id'     => 1000,
//                'website_id'    => 1,
//                'store_id'      => 1,
//                'firstname'     => 'First name',
//                'lastname'      => 'Last name',
//                'email'         => 'abcd@example.com'
//            )
//        );
//        $payload = array_values(Mage::getModel('emarsys_suite2/api_payload_customer_item', $customer)->toArray());
//        $this->assertEquals(array(1000, '2', 'First name', 'Last name', 'abcd@example.com', 'default', 'base'), $payload, 'Payload is different than expected');
//    }
    
    protected function _apiQueryByEmail($email)
    {
        return array('key_id' => static::$_config->getEmarsysEmailKeyId(), static::$_config->getEmarsysEmailKeyId()=> $email);
    }
    
    protected function _deleteEmail($email)
    {
        try {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            /* @var $connection Magento_Db_Adapter_Pdo_Mysql */
            $connection->delete($connection->getTableName('customer_entity'), array('email = ?' => $email));
            $connection->delete($connection->getTableName('newsletter_subscriber'), array('subscriber_email = ?' => $email));
            static::$_client->post('contact/delete', $this->_apiQueryByEmail($email));
        } catch (Exception $e) {
        }

        return $this;
    }
    
    protected function _createCustomer($email)
    {
        return Mage::getModel('customer/customer')->setData(
            array(
                'website_id'    => 1,
                'store_id'      => 1,
                'firstname'     => 'First name',
                'lastname'      => 'Last name',
                'email'         => $email
            )
        )->save()->getId();
    }
    
    protected function _createSubscriber($email, $customerId = 0)
    {
        return Mage::getModel('newsletter/subscriber')->setData(
            array(
                'store_id'          => 1,
                'subscriber_email'  => $email,
                'subscriber_status' => 1,
                'customer_id'       => $customerId,
            )
        )->save()->getId();
    }
    
    protected function _customerDuplicatesCheckResult($result, $email)
    {
        $this->assertArrayHasKey('data', $result, 'Invalid API response');
        if (isset($result['data'])) {
            $this->assertArrayHasKey('ids', $result['data'], 'Invalid API response');
            if (isset($result['data']['errors']) && !empty($result['data']['errors'])) {
                $this->fail('Got API Error: ' . implode(', ', $result['data']['errors'][$email]));
            }

            if (isset($result['data']['ids'])) {
                $this->assertArrayHasKey($email, $result['data']['ids'], 'Email not found in suite');
            }
        }
    }
    
    protected function _setEmailAsKey($flag = false)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            Mage::app()->getStore(Mage::app()->getDefaultStoreView()->getId())->setConfig('emarsys_suite2_contacts_sync/settings/email_as_id', $flag);
        } else {
            Mage::app()->getStore(static::STORE)->setConfig('emarsys_suite2_contacts_sync/settings/email_as_id', $flag);
        }
    }
    
    public function testSubscriberAndCustomerUpdateEmailKey()
    {
        $this->_setEmailAsKey(1);
        $this->_testSubscriberAndCustomerUpdate();
    }
    
    public function testSubscriberAndCustomerUpdateDefaultKey()
    {
        $this->_setEmailAsKey(0);
        $this->_testSubscriberAndCustomerUpdate();
    }
    
    public function testCustomerAndSubscriberUpdateEmailKey()
    {
        $this->_setEmailAsKey(1);
        $this->_testCustomerAndSubscriberUpdate();
    }
    
    public function testCustomerAndSubscriberUpdateDefaultKey()
    {
        $this->_setEmailAsKey(0);
        $this->_testCustomerAndSubscriberUpdate();
    }
    
    
    protected function _testSubscriberAndCustomerUpdate()
    {
        $this->_deleteEmail($this->_email);
        $this->debug()->clear();
        if (Mage::registry(Emarsys_Suite2_Model_Observer::CUSTOMER_SAVE_TRIGGER . '_' . md5($this->_email))) {
            Mage::unregister(Emarsys_Suite2_Model_Observer::CUSTOMER_SAVE_TRIGGER . '_' . md5($this->_email));
        }        
        
        $subscriberId = $this->_createSubscriber($this->_email);
        $this->_assertCallsAmount('contact/create_if_not_exists=1', 'PUT', 1, 'Subscriber>Customer(S) error: ');
        $this->debug()->clear();
        
        $customerId = $this->_createCustomer($this->_email);
        $this->_assertCallsAmount('contact/create_if_not_exists=1', 'PUT', 1, 'Subscriber>Customer(C) error: ');
        $this->debug()->clear();
        
        $this->_duplicateRecordCheck($this->_email);
        $this->_recordDataCheck($customerId, $subscriberId);
    }
    
    protected function _testCustomerAndSubscriberUpdate()
    {
        $this->_deleteEmail($this->_email);
        $this->debug()->clear();
        if (Mage::registry(Emarsys_Suite2_Model_Observer::CUSTOMER_SAVE_TRIGGER . '_' . md5($this->_email))) {
            Mage::unregister(Emarsys_Suite2_Model_Observer::CUSTOMER_SAVE_TRIGGER . '_' . md5($this->_email));
        }

        $customerId = $this->_createCustomer($this->_email);
        
        $this->_assertCallsAmount('contact/create_if_not_exists=1', 'PUT', 1, 'Customer>Subscriber(C) error: ');
        $this->debug()->clear();
        
        if (Mage::registry(Emarsys_Suite2_Model_Observer::CUSTOMER_SAVE_TRIGGER . '_' . md5($this->_email))) {
            Mage::unregister(Emarsys_Suite2_Model_Observer::CUSTOMER_SAVE_TRIGGER . '_' . md5($this->_email));
        }
        
        $subscriberId = $this->_createSubscriber($this->_email, $customerId);
        $this->_assertCallsAmount('contact/create_if_not_exists=1', 'PUT', 1, 'Customer>Subscriber(S) error: ');
        $this->debug()->clear();
        
        $this->_duplicateRecordCheck($this->_email);
        $this->_recordDataCheck($customerId, $subscriberId);
    }
    
    protected function _duplicateRecordCheck($email)
    {
        $result = static::$_client->post('contact/checkids', array('key_id' => static::$_config->getEmarsysEmailKeyId(), 'external_ids' => array($email)));
        $this->assertArrayHasKey('data', $result, 'Invalid API response');
        if (isset($result['data'])) {
            $this->assertArrayHasKey('ids', $result['data'], 'Invalid API response');
            if (isset($result['data']['errors']) && !empty($result['data']['errors'])) {
                $this->fail('Got API Error: ' . implode(', ', $result['data']['errors'][$email]));
            }

            if (isset($result['data']['ids'])) {
                $this->assertArrayHasKey($email, $result['data']['ids'], 'Email not found in suite');
            }
        }
    }
    
    protected function _recordDataCheck($customerId = null, $subscriberId = null)
    {
        $data = array(
            'keyId' => ($customerId ? static::$_config->getEmarsysCustomerKeyId() : static::$_config->getEmarsysSubscriberKeyId()),
            'keyValues' => ($customerId ? array($customerId) : array($subscriberKeyId)),
            'fields' => array(static::$_config->getEmarsysCustomerKeyId(), static::$_config->getEmarsysSubscriberKeyId())
        );
        $result = static::$_client->post('contact/getdata', $data);
        if (isset($result['data']) && isset($result['data']['result']) && isset($result['data']['result'][0])) {
            $result = $result['data']['result'][0];
            if ($customerId) {
                $this->assertArrayHasKey(static::$_config->getEmarsysCustomerKeyId(), $result, 'CustomerID not found in record');
                $this->assertEquals($customerId, $result[static::$_config->getEmarsysCustomerKeyId()], 'Customer ID is invalid for saved record');
            }

            if ($subscriberId) {
                $this->assertArrayHasKey(static::$_config->getEmarsysSubscriberKeyId(), $result, 'SubscriberID not found in record');
                $this->assertEquals($subscriberId, $result[static::$_config->getEmarsysSubscriberKeyId()], 'Customer ID is invalid for saved record');
            }
        } else {
            $this->fail('No record data found in Emarsys after save of customer/subscriber');
        }
    }
}