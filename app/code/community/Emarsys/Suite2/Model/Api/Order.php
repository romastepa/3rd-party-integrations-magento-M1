<?php

class Emarsys_Suite2_Model_Api_Order extends Emarsys_Suite2_Model_Api_Abstract
{
    protected $_cleanEntitiesOnEachWebsiteExport = false;
    protected $_processedItems = array();
    protected $_exportedIds = array();
    protected $_processedCreditmemos = array();
    protected $_categoryNames;
    
    /**
     * 
     * @return Mage_Sales_Model_Order
     */
    protected function _getEntity()
    {
        return Mage::getSingleton('sales/order');
    }
    
    /**
     * @inheritdoc
     */
    public function isExportEnabled()
    {
        return $this->_getConfig()->isOrdersExportEnabled();
    }
    
    /**
     * Tests FTP connection
     * 
     * @param array $params
     */
    public function testFtp($params)
    {
        $result = 0;
        $client = new Varien_Io_Ftp();
        $client->open($params);
        $filename = 'test.txt';
        if ($params['dir']) {
            $filename = rtrim($params['dir'], '/') .'/'. $filename;
        }

        if ($client->write($filename, 'text') && $client->rm($filename)) {
            $result = 1;
        }

        $client->close();
        if ($result === 0) {
            $result = "Unknown FTP error. Please check active/passive mode setting and ensure your firewall is configured correctly to work with either one.";
        }

        return $result;
    }
    
    /**
     * Uploads file to remote FTP
     * 
     * @param string $src Filename
     * 
     * @return bool|int   Result
     */
    protected function _uploadFile($src, $filename)
    {
        if (!is_readable($src)) {
            $this->log('Error uploading "' . $src . '": file is not readable');
            return false;
        }

        $client = new Varien_Io_Ftp();
        $this->log('Starting upload of "' . $src . '"');
        $client->open(
            array(
                'ssl' => $this->_getConfig()->getSmartinsightFtpSsl(),
                'host' => $this->_getConfig()->getSmartinsightFtpHost(),
                'user' => $this->_getConfig()->getSmartinsightFtpUser(),
                'password' => $this->_getConfig()->getSmartinsightFtpPassword(),
                'ssl' => $this->_getConfig()->getSmartinsightFtpSsl(),
                'passive' => $this->_getConfig()->getSmartinsightFtpPassive()
            )
        );
        if ($dir = $this->_getConfig()->getSmartinsightFtpDir()) {
            $filename = rtrim($dir, '/') .'/'. $filename;
        }

        $this->log('Uploading "' . $src . '"');
        $result = $client->write($filename, $src);
        $client->close();
        $this->log('Uploaded "' . $src . '"');
        return $result;
    }
    
    /**
     * Returns category name by id
     * 
     * @param int $id
     * 
     * @return string
     */
    protected function _getCategoryName($id)
    {
        if (!isset($this->_categoryNames[$id])) {
            $this->_categoryNames[$id] = Mage::getModel('catalog/category')->load($id)->getName();
        }

        return $this->_categoryNames[$id];
    }
    
    /**
     * Returns category string name with path
     * 
     * @param int $id
     * 
     * @return string
     */
    protected function _getCategoryString($id)
    {
        $categoryPath = explode(
            '/', Mage::getModel('catalog/category')
                ->setStoreId($this->_getConfig()->getStoreId())
            ->load($id)->getPath()
        );
        // Remove two first categories as they're not visible //
        
        array_shift($categoryPath);
        array_shift($categoryPath);
        
        $result = array();
        foreach ($categoryPath as $categoryId) {
            $result[] = $this->_getCategoryName($categoryId);
        }

        return implode('>', $result);
    }
    
    /**
     * Returns temp filename for product data
     * 
     * @return string
     */
    protected function _getProductDataTmpFilename()
    {
        $key = 'products|' . $this->_getConfig()->getSmartinsightFtpHost() . '|' . $this->_getConfig()->getSmartinsightFtpUser() . '|' . $this->_getConfig()->getSmartinsightFtpDir();
        return Mage::getBaseDir('var') . '/' . md5($key) . '.csv';
    }
    
    /**
     * Returns temp filename for order data
     * 
     * @return string
     */
    protected function _getOrderDataTmpFilename()
    {
        $key = 'orders|' . $this->_getConfig()->getSmartinsightFtpHost() . '|' . $this->_getConfig()->getSmartinsightFtpUser() . '|' . $this->_getConfig()->getSmartinsightFtpDir();
        return Mage::getBaseDir('var') . '/' . md5($key) . '.csv';
    }
    
    /**
     * Exports product data
     * 
     * @param array $skus
     */
    protected function _exportProductData($skus)
    {
        $io = new Varien_Io_File();
        $filename = $this->_getProductDataTmpFilename();
        $hasHeader = $io->fileExists($filename) && filesize($filename);
        $io->cd(Mage::getBaseDir('var'));
        $this->log('Opening file "' . $filename . '" for purchased products data export');
        $io->streamOpen($filename, 'a');
        
        $this->_categoryNames = array();
        $chunkSize = 1000;
        $skus = array_chunk($skus, $chunkSize); // split to arrays of 1000 skus //
        if (!isset($this->_processedItems[$filename])) {
            $this->_processedItems[$filename] = array();
        }

        foreach ($skus as $skuArray) {
            $collection = Mage::getResourceModel('catalog/product_collection')
                    ->setStoreId($this->_getConfig()->getStoreId())
                    ->addAttributeToSelect('name')
                    ->addCategoryIds()
                    ->addAttributeToFilter('sku', array('IN' => $skuArray));
            foreach ($collection as $item) {
                if (!$hasHeader) {
                    // Write refund item sku //
                    $refundItem = array(
                        'website_id' => $this->_getConfig()->getWebsiteId(),
                        'item' => 0,
                        'title' => 'refund',
                        'category' => ''
                    );
                    $io->streamWriteCsv(array_keys($refundItem), ';', '"');
                    $hasHeader = true;
                }

                // Skip record if its there already //
                if (isset($this->_processedItems[$filename][$item->getId()])) {
                    continue;
                }

                $row['website_id'] = $this->_getConfig()->getWebsiteId();
                $row['item'] = $item->getSku();
                $row['title'] = $item->getName();
                $_categories = array();
                foreach ($item->getCategoryIds() as $_categoryId) {
                    if ($_categoryString = $this->_getCategoryString($_categoryId)) {
                        $_categories[] = $_categoryString;
                    }

                    if (count($_categories) == 3) {
                        break;
                    }
                }

                $row['category'] = implode('|', $_categories);
                $io->streamWriteCsv($row, ';', '"');
                $this->_processedItems[$filename][$item->getId()] = true;
            }
        }

        $this->log('Generated file "' . $filename. '"');
        $io->streamClose($filename);
    }
    
    /**
     * @inheritdoc
     */
    protected function _exportWebsiteData($website)
    {
        $this->_exportEntityData($website, $this->_getEntity());
        $this->_exportEntityData($website, Mage::getModel('sales/order_creditmemo'));
    }
    
    /**
     * @inheritdoc
     */
    protected function _exportEntityData($website, $entity)
    {
        $io = new Varien_Io_File();
        $filename = $this->_getOrderDataTmpFilename();
        $hasHeader = $io->fileExists($filename) && filesize($filename);
        $io->cd(Mage::getBaseDir('var'));
        $this->log('Opening file "' . $filename . '" for order export');
        $io->streamOpen($filename, 'a');
        $skus = array();
        if ($entity instanceof Mage_Sales_Model_Order) {
            $payloadCode = 'emarsys_suite2/api_payload_order';
            $collectionCode = 'sales/order_collection';
        } else {
            $payloadCode = 'emarsys_suite2/api_payload_creditmemo';
            $collectionCode = 'sales/order_creditmemo_collection';
        }

        /* @var $queue Emarsys_Suite2_Model_Resource_Queue_Collection */
        $queue = null;
        while ($queue = Mage::getSingleton('emarsys_suite2/queue')->getNextBunch($entity, $website->getId(), $queue)) {
            $entityIds = $queue->getColumnValues('entity_id');
            $collection = Mage::getResourceModel($collectionCode)->addFieldToFilter('entity_id', array('in' => $entityIds));
            foreach ($collection as $item) {
                if ($entity instanceof Mage_Sales_Model_Order) {
                    $this->_processedEntities[] = $item->getId();
                } else {
                    $this->_processedCreditmemos[] = $item->getId();
                }

                foreach (Mage::getModel($payloadCode, $item)->toArray() as $row) {
                    if (!$hasHeader) {
                        $io->streamWriteCsv(array_keys($row), ';', '"');
                        $hasHeader = true;
                    }

                    $io->streamWriteCsv($row, ';', '"');
                    $skus[$row['item']] = null;
                }
            }
        }
	/* Script to add header row in CSV if the file is empty */
        if (!filesize($filename)) {
            $emptyFileHeader = $this->getSalesOrderHeader();
            $io->streamWriteCsv($emptyFileHeader, ';', '"');
        }

        $io->streamClose();
        $this->log('Generated file "' . $filename . '"');
        $this->_exportProductData(array_keys($skus));
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    protected function _beforeExport()
    {
        $io = new Varien_Io_File();
        foreach (Mage::app()->getWebsites() as $website) {
            $this->_getConfig()->setWebsite($website);
            if ($io->fileExists($this->_getOrderDataTmpFilename())) {
                $io->rm($this->_getOrderDataTmpFilename());
            }

            if ($io->fileExists($this->_getProductDataTmpFilename())) {
                $io->rm($this->_getProductDataTmpFilename());
            }
        }
    }
    
    /**
     * @inheritdoc
     */
    protected function _afterExport()
    {
        $io = new Varien_Io_File();
        foreach (Mage::app()->getWebsites() as $website) {
//            $this->_processedEntities = array();
            $this->_getConfig()->setWebsite($website);
            $dstFilename = 'sales_items_'. date('Ymd') . '.csv';
            if ($this->_uploadFile($this->_getOrderDataTmpFilename(), $dstFilename)) {
                $dstFilename = 'products_'. date('Ymd') . '.csv';
                if ($this->_uploadFile($this->_getProductDataTmpFilename(), $dstFilename)) {
                    if (!$this->_getConfig()->getDebug()) {
                        // Remove temp files //
                        $io->rm($this->_getProductDataTmpFilename());
                        $io->rm($this->_getOrderDataTmpFilename());
                    }

                    // Clean up queue //
                    if ($this->_processedEntities) {
                        $this->cleanupQueue(array_unique($this->_processedEntities));
                        Mage::getResourceModel('emarsys_suite2/flag_order')->massSetExported($this->_processedEntities);
                        $this->_processedEntities = array();
                    }

                    if ($this->_processedCreditmemos) {
                        Mage::getModel('emarsys_suite2/queue')
                                ->setMainEntity(Mage::getModel('sales/order_creditmemo'))
                                ->removeEntities($this->_processedCreditmemos);
                        Mage::getResourceModel('emarsys_suite2/flag_creditmemo')->massSetExported($this->_processedCreditmemos);
                        $this->_processedCreditmemos = array();                        
                    }
                } else {
                    $this->log('Failed uploading product data to SmartInsight FTP');
                }
            } else {
                $this->log('Failed uploading order data to SmartInsight FTP');
            }
        }
    }
    
    protected function _afterExportWebsiteData($website)
    {
        return $this;
    }

    /**
     * Get Sales Order Header
     *
     * @return array $header
     */
    public function getSalesOrderHeader()
    {
        return array(
                'website_id',
                'order',
                'date',
                'customer',
                'item',
                'quantity',
                'unit_price',
                'c_sales_amount',
            );
    }
}
