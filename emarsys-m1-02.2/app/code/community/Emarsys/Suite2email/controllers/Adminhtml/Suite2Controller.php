<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
require_once("Emarsys/Suite2/controllers/Adminhtml/Suite2Controller.php");

class Emarsys_Suite2email_Adminhtml_Suite2Controller extends Emarsys_Suite2_Adminhtml_Suite2Controller
{
    /**
     * Queues 2 years order export
     */
    public function exportAllOrdersAction()
    {
        try {
            set_time_limit(0);
            $pageNum = 1;
            $result = false;
            try {
                while ($this->_queueOrdersBatch($pageNum++)) {
                    $result = true;
                }
                if ($result) {
                    Mage::helper('emarsys_suite2/adminhtml')->scheduleCronjob('orders');
                    printf(1);
                } else {
                    printf ("Error: No paid orders found");
                }
            } catch (Exception $e) {
                Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
                printf("Error: {$e->getMessage()}");
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Batch queueing
     *
     * @param type $pageNum
     *
     * @return boolean
     */
    protected function _queueOrdersBatch($pageNum)
    {
        try {
            /* Multiwebsite Support*/
            foreach (Mage::app()->getWebsites() as $website) {
                $websiteCode = $website->getData('code');
                $pageSize = Mage::helper('emarsys_suite2/adminhtml')->getBatchSize();
                /* @var $collection Mage_Sales_Model_Resource_Order_Collection */
                $collection = Mage::getResourceModel('sales/order_collection')
                    ->addFieldToFilter('created_at', array('gteq' => new Zend_Db_Expr('CURRENT_DATE - INTERVAL 2 YEAR')))
                    ->addFieldToFilter('status', array('IN' => Mage::helper('suite2email')->getOrderStatuses($websiteCode)))
                    ->setPage($pageNum, $pageSize);
                $orderIds = $collection->getColumnValues('entity_id');

                if ($collection->getCurPage() < $pageNum) {
                    return false;
                }

                if ($collection->count()) {
                    // Queue collection
                    Mage::getSingleton('emarsys_suite2/queue')->addCollection($collection);
                    $collection = Mage::getResourceModel('sales/order_creditmemo_collection')
                        ->addFieldToFilter('created_at', array('gteq' => new Zend_Db_Expr('CURRENT_DATE - INTERVAL 2 YEAR')))
                        ->addFieldToFilter('state', Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED)
                        ->addFieldToFilter('order_id', array('IN' => $orderIds));

                    Mage::getSingleton('emarsys_suite2/queue')->addCollection($collection);
                    return true;
                } else {
                    return false;
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
