<?php

class Emarsys_Suite2_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MAX_ITEMS_COUNT = 5;
    /**
     * Returns config object
     * 
     * @return Emarsys_Suite2_Model_Config
     */
    protected function _getConfig($website=null)
    {
        if (Mage::app()->getStore()->isAdmin() && is_null($website)) {
            $website = Mage::getSingleton('adminhtml/config_data')->getWebsite();
            if ($website) {
                Mage::getSingleton('emarsys_suite2/config')->setWebsite(Mage::app()->getWebsite($website));
            } else {
//                Mage::getSingleton('emarsys_suite2/config')->setWebsite(0);
            }
        } else {
            Mage::getSingleton('emarsys_suite2/config')->setWebsite(Mage::app()->getWebsite($website));
        }

        return Mage::getSingleton('emarsys_suite2/config');
    }
    
    public function __construct() 
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            Mage::getSingleton('emarsys_suite2/config')->setWebsite(Mage::app()->getWebsite());
        }
    }
    
    public function getPaidOrderStates()
    {
        return array(
            Mage_Sales_Model_Order::STATE_CLOSED,
            Mage_Sales_Model_Order::STATE_COMPLETE,
            Mage_Sales_Model_Order::STATE_PROCESSING
        );
    }
    
    public function getAPIUrl()
    {
        switch ($this->_getConfig()->getSettingsApiEndpoint()) {
            case 'custom':
                return $this->_getConfig()->getSettingsCustomApiEndpoint();
            default:
                return Mage::getStoreConfig('emarsys_suite2/api_endpoints/' . $this->_getConfig()->getSettingsApiEndpoint());
        }
    }
    
    /**
     * Returns API client object
     * 
     * @return Emarsys_Suite2_Model_Api
     */
    public function getClient()
    {
        $url = $this->getAPIUrl();
        $username = $this->_getConfig()->getSettingsApiUsername();
        $password = $this->_getConfig()->getSettingsApiPassword();
        return Mage::getModel('emarsys_suite2/api', array('api_url' => $url, 'api_username' => $username, 'api_password' => $password));
    }
    
    /**
     * Logs line
     * 
     * @param mixed $line
     */
    public function log($line, $srcClass = null)
    {
        if (!$this->_getConfig()->getDebug()) {
            return;
        }

        if ($line instanceof Exception) {
            $line = '[Ex] ' . $line->getMessage();
        } elseif (!is_string($line)) {
            $line = print_r($line, true);
        }

        if ($srcClass) {
            $prefix = str_replace('Emarsys_Suite2_Model_', '', get_class($srcClass));
        } else {
            $prefix = 'External';
        }

        $line = sprintf('[PID: %010d] [%s] %s', getmypid(), $prefix, $line);
        Mage::log($line, LOG_DEBUG, 'emarsys.log', true);
    }
    
    public function isEnabled()
    {
        return Mage::getSingleton('emarsys_suite2/config')->isEnabled();
    }
    
    /**
     * Returns customer cart data
     * 
     * @param Mage_Customer_Model_Customer $customer
     * 
     * @return array
     */
    public function getCustomerCartData($customer)
    {
        return $this;
        /**
        'c_last_cart_product_1'   => 'Last added to cart product 1',
        'c_last_cart_product_2'   => 'Last added to cart product 2',
        'c_last_cart_product_3'   => 'Last added to cart product 3',
        'c_last_cart_product_4'   => 'Last added to cart product 4',
        'c_last_cart_product_5'   => 'Last added to cart product 5'
         */
        if (Mage::app()->getStore()->isAdmin()) {
            $customerId = $customer->getId();
            $quote = Mage::getModel('sales/quote')->setWebsite(Mage::app()->getWebsite($customer->getWebsiteId()));
            $quote->getResource()->loadByCustomerId($quote, $customerId);
        } else {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
        }

        /* @var $quote Mage_Sales_Model_Quote */
        $index = 1;
        $result = array();
        if ($quote && $quote->hasItems()) {
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($item->getId() || $item->getProductType()!= Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                    $result['c_last_cart_product_' . $index++] = $item->getName();
                    if ($index > 5) {
                        break;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Returns customer orders' data
     * 
     * @param Mage_Customer_Model_Customer $customer
     * 
     * @return array
     */
    public function getCustomersOrdersData($_customer)
    {
        return array();
        if (is_object($_customer)) {
            $customerIds = $_customer->getId();
        } elseif (is_array($_customer)) {
            $customerIds = $_customer;
        } else {
            $customerIds = array($_customer);
        }
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        /* @var $connection Magento_Db_Adapter_Pdo_Mysql */
        $tableNameOrders = Mage::getModel('sales/order')->getResource()->getTable('order');
        $select = $connection->select();
        /* @var $select Varien_Db_Select */
        // Get total orders data //
        $select->from($tableNameOrders, null)
               ->where('customer_id IN (?)', $customerIds)
               ->where('state != ?', Mage_Sales_Model_Order::STATE_CANCELED) // pending orders also needs to be included in result
               ->columns(
                   array(
                   //                       'c_last_order_time'      => 'DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s")',
                   //                       'c_last_order_total'     => new Zend_Db_Expr('ROUND(grand_total, 2)'),
                       'c_avg_sales'            => new Zend_Db_Expr('ROUND(AVG(grand_total), 2)'),
                       'c_num_orders'           => new Zend_Db_Expr('COUNT(entity_id)'),
                       'c_lifetime_order_total' => new Zend_Db_Expr('ROUND(SUM(grand_total), 2)'),
                       'customer_id'
                   )
               )
               ->distinct()
               ->group('customer_id')
               ->order('created_at DESC');
        $result = array();
        Varien_Profiler::start('EmarsysSuite2::getCustomersOrdersData');
        foreach ($connection->fetchAll($select) as $item) {
            $result[$item['customer_id']] = $item;
            unset($result[$item['customer_id']]['customer_id']);
        }

        $select = $connection->select();
        $select->from($tableNameOrders, null)
               ->where('customer_id IN (?)', $customerIds)
               ->where('state != ?', Mage_Sales_Model_Order::STATE_CANCELED) // pending orders also needs to be included in result
               ->columns(
                   array(
                       'customer_id',
                       'grand_total',
                       'created_at',
                   )
               )->order('created_at DESC');
        foreach ($connection->fetchAll($select) as $item) {
            $customerId = $item['customer_id'];
            if (!isset($result[$customerId]['c_last_order_time'])) {
                $result[$customerId]['c_last_order_time'] = $this->formatDt($item['created_at']);
                $result[$customerId]['c_last_order_total'] = $this->formatPrice($item['grand_total']);
            }
        }

        Varien_Profiler::stop('EmarsysSuite2::getCustomersOrdersData');
        if (is_array($_customer)) {
            return $result;
        } else {
            return current($result);
        }
    }
    
    public function formatDt($dt)
    {
        if (!($dt instanceOf Zend_Date)) {
            $dt = new Zend_Date($dt);
        }

        return $dt->toString('YYYY-MM-dd HH:mm:ss');
    }
    
    public function formatPrice($price)
    {
        return sprintf('%01.2f', round($price, 2));
    }
}
