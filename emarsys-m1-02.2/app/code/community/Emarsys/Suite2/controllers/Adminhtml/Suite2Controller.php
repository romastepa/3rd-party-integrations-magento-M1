<?php
class Emarsys_Suite2_Adminhtml_Suite2Controller extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/emarsys_suite2');
    }
    
    /**
     * Returns true if cronjob is scheduled
     * 
     * @param type $which
     * 
     * @return boolean
     */
    protected function _isCronjobScheduled($which)
    {
        $jobCode = 'emarsys_suite2_cron_export_' . $which;
        try {
            $cron = Mage::getResourceModel('cron/schedule_collection')
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter('status', array('IN' => array(Mage_Cron_Model_Schedule::STATUS_PENDING, Mage_Cron_Model_Schedule::STATUS_RUNNING)))
                ->getFirstItem();
            return ($cron->getId() > 0);
        } catch (Exception $ex) {
            return false;
        }
    }
    
    public function eventInfoAction()
    {
        $eventId = $this->getRequest()->getParam('eventId');
        $event = Mage::getModel('emarsys_suite2/email_event')->loadByEventId($eventId);
        if ($event->getId() && ($result = Mage::helper('emarsys_suite2/adminhtml')->isEventRegistered($eventId))) {
            $result = Mage::helper('core')->jsonEncode($result);
        } else {
            $result = Mage::helper('core')->jsonEncode(array());
        }

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody($result);
    }
    
    /**
     * Gets fields from request
     * 
     * @param string $groupName
     * 
     * @return array
     */
    protected function _getBackendFields($groupName)
    {
        $params = $this->getRequest()->getParam('groups');
        $result = array();
        if (isset($params[$groupName])) {
            foreach ($params[$groupName]['fields'] as $field => $value) {
                $result[$field] = $value['value'];
            }
        }

        return $result;
    }
    
    /**
     * Pings API 
     * 
     * @param array $params
     * 
     * @return string
     */
    protected function _pingSettings($params)
    {
        return Mage::getSingleton(
            'emarsys_suite2/api',
            array(
                    'api_url'       => Mage::helper('emarsys_suite2')->getAPIUrl(),
                    'api_username'  => $params['api_username'],
                    'api_password'  => $params['api_password'],
                )
        )->ping();
    }
    
    /**
     * Pings SFTP
     * 
     * @param array $params
     * 
     * @return string
     */
    protected function _pingSmartinsightFtp($params)
    {
        return Mage::getSingleton('emarsys_suite2/api_order')->testFtp($params);
    }
    
    /**
     * Pings target server action
     */
    public function pingAction()
    {
        $target = str_replace('emarsys_suite2_', '', $this->getRequest()->getParam('target'));
        
        // get the last underscored element name to get the array key //
        $_tmp = explode('_', $target);
        $targetGroupName = array_pop($_tmp);
        
        $params = $this->_getBackendFields($targetGroupName);
        $methodName = '_ping' . uc_words($target, '');
        
        if (method_exists($this, $methodName)) {
            try {
                printf($this->$methodName($params));
            } catch (Exception $e) {
                printf($e->getMessage());
            }
        } else {
            printf('Method is not defined in controller: ' . $methodName);
        }
    }
    
    /**
     * Queues collection
     * 
     * @param mixed  $collection  Collection
     * @param int    $pageNum     Page number
     * 
     * @return boolean
     */
    protected function _queueCollectionBatch($collection, $pageNum)
    {
        $pageSize = Mage::helper('emarsys_suite2/adminhtml')->getBatchSize();
        
        $collection->setCurPage($pageNum)->setPageSize($pageSize)->load();
        
        if ($collection->getCurPage() < $pageNum) {
            return false;
        }

        /* @var $collection Mage_Core_Model_Resource_Db_Collection_Abstract */
        if ($collection->count()) {
            Mage::getSingleton('emarsys_suite2/queue')->addCollection($collection);
            return true;
        }
    }
    
    /**
     * Queues customers export
     */
    public function exportCustomersAction()
    {
        if ($this->_isCronjobScheduled('subscribers')) {
            printf('Subscribers export is already scheduled. Please wait until export is finished.');
            return;
        }

        set_time_limit(0);
        $pageNum = 1;
        $result = false;
        try {
            while ($this->_queueCollectionBatch(Mage::getResourceModel('customer/customer_collection'), $pageNum++)) {
                $result = true;
            }

            if ($result) {
                Mage::helper('emarsys_suite2/adminhtml')->scheduleCronjob('customers');
                printf(1);
            } else {
                printf("Error: No customers found");
            }
        } catch (Exception $e) {
            printf("Error: {$e->getMessage()}");
        }
    }
    
    public function exportSubscribersAction()
    {
        if ($this->_isCronjobScheduled('customers')) {
            printf('Customers export is already scheduled. Please wait until export is finished.');
            return;
        }

        try {
            $pageNum = 0;
            // Queue all anonymous subscribers
            while ($this->_queueCollectionBatch(Mage::getResourceModel('newsletter/subscriber_collection'), $pageNum++)) {
                $result = true;
            }

            $pageNum = 0;
            while ($this->_queueCollectionBatch(
                Mage::getResourceModel('customer/customer_collection')
                    ->joinField('subscriber_id', 'newsletter/subscriber', 'subscriber_id', 'customer_id = entity_id'),
                $pageNum++
            )) {
                $result = true;
            }

            if ($result) {
                Mage::helper('emarsys_suite2/adminhtml')->scheduleCronjob('subscribers');
                printf(1);
            } else {
                printf('No subscribers found');
            }
        } catch (Exception $e) {
            printf("Error: {$e->getMessage()}");
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
        $pageSize = Mage::helper('emarsys_suite2/adminhtml')->getBatchSize();
        /* @var $collection Mage_Sales_Model_Resource_Order_Collection */
        $collection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('created_at', array('gteq' => new Zend_Db_Expr('CURRENT_DATE - INTERVAL 2 YEAR')))
                ->addFieldToFilter('state', array('IN' => Mage::helper('emarsys_suite2')->getPaidOrderStates()))
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
    
    /**
     * Queues 2 years order export
     */
    public function exportAllOrdersAction()
    {
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
                printf("Error: No paid orders found");
            }
        } catch (Exception $e) {
            printf("Error: {$e->getMessage()}");
        }
    }
    
    /**
     * Clean Emarsys debug logs
     */
    public function cleanLogsAction()
    {
        $io = new Varien_Io_File();
        foreach (Mage::helper('emarsys_suite2/adminhtml')->getTrackedLogFiles() as $logFile) {
            $filename = Mage::getBaseDir('log') . DS . $logFile;
            if ($io->fileExists($filename)) {
                $io->rm($filename);
            }
        }

        printf(1);
    }
    
    /**
     * Clean Emarsys debug logs
     */
    public function downloadLogsAction()
    {
        $result = false;
        $io = new Varien_Io_File();
        if (!class_exists('ZipArchive')) {
            printf('ZipArchive class is not found');
            return;
        }

        $zip = new ZipArchive();
        $zipName = Mage::getBaseDir('log') . DS . 'emarsys-debuglog-' . date('Ydm-His') . '.zip';
        if (!$zip->open($zipName, ZipArchive::CREATE)) {
            printf('Unable to write to ' . $zipName);
            return;
        };
        foreach (Mage::helper('emarsys_suite2/adminhtml')->getTrackedLogFiles() as $logFile) {
            $filename = Mage::getBaseDir('log') . DS . $logFile;
            if ($io->fileExists($filename)) {
                $result = true;
                $zip->addFile($filename, $logFile);
            }
        }

        $zip->close();
        if ($result) {
            printf('<a href="%s">Click this link to download</a>', $this->getUrl('*/*/getlog', array('_query' => array('filename' => basename($zipName)))));
        } else {
            printf('No logs found');
        }
    }
    
    /**
     * Downloads log file
     */
    public function getlogAction()
    {
        $filename = basename($this->getRequest()->getParam('filename'));
        $localFilename = Mage::getBaseDir('log') . DS . $filename;
        $io = new Varien_Io_File();
        if ($io->fileExists($localFilename)) {
            $io->open(array('path' => Mage::getBaseDir('log')));
            $this->_prepareDownloadResponse($filename, $io->read($localFilename));
        } else {
            $this->_redirectError(Mage_Core_Controller_Varien_Action::PARAM_NAME_ERROR_URL);
        }
    }
}
